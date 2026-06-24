<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\PurchaseRequest;
use App\Models\Transaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentService
{
    protected string $driver;
    protected string $campayBaseUrl;
    protected ?string $campayToken;
    protected ?string $campayUsername;
    protected ?string $campayPassword;
    protected int $campayTimeout;
    protected int $campayConnectTimeout;
    protected bool $campayFallbackToLocal;

    /**
     * Create a new class instance.
     */
    public function __construct(
        protected NotificationService $notificationService,
        protected EscrowService $escrowService,
    ) {
        $this->driver = config('services.payments.driver', 'local');
        $this->campayBaseUrl = rtrim(config('services.campay.base_url'), '/');
        $this->campayToken = config('services.campay.token');
        $this->campayUsername = config('services.campay.username');
        $this->campayPassword = config('services.campay.password');
        $this->campayTimeout = (int) config('services.campay.timeout', 20);
        $this->campayConnectTimeout = (int) config('services.campay.connect_timeout', 10);
        $this->campayFallbackToLocal = (bool) config('services.campay.fallback_to_local', false);
    }

    public function initiate(PurchaseRequest $purchaseRequest, array $data): Transaction
    {
        $sellerPhone = $purchaseRequest->seller->studentProfile->phone;
        $paymentReference = null;
        $holdLocally = $this->driver === 'local';

        if ($this->driver === 'campay') {
            try {
                $paymentReference = $this->initiateCampayPayment($purchaseRequest, $data);
            } catch (ConnectionException|RequestException $exception) {
                if (!$this->campayFallbackToLocal) {
                    throw $exception;
                }

                report($exception);
                $holdLocally = true;
            }
        }

        $transaction = Transaction::create([
            'purchase_request_id' => $purchaseRequest->id,
            'buyer_id'            => $purchaseRequest->buyer_id,
            'seller_id'           => $purchaseRequest->seller_id,
            'amount'              => $purchaseRequest->total_price,
            'kpay_payment_id'     => $paymentReference,
            'status'              => TransactionStatus::INITIATED,
            'payment_method'      => $data['payment_method'],
            'buyer_phone'         => $data['buyer_phone'],
            'seller_phone'        => $sellerPhone,
        ]);

        $this->notificationService->paymentInitiated($purchaseRequest->buyer);

        if ($holdLocally) {
            $this->escrowService->hold($transaction);

            return $transaction->fresh();
        }

        return $transaction;
    }

    public function checkStatus(?string $paymentReference): string
    {
        if ($this->driver === 'local') {
            return 'successful';
        }

        if ($this->driver !== 'campay' || !$paymentReference) {
            return 'failed';
        }

        try {
            $response = $this->campayRequest()
                ->get("{$this->campayBaseUrl}/api/transaction/{$paymentReference}/")
                ->throw()
                ->json();
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            return $this->campayFallbackToLocal ? 'successful' : 'pending';
        }

        return match (strtoupper($response['status'] ?? 'FAILED')) {
            'PENDING', 'PROCESSING' => 'pending',
            'SUCCESSFUL' => 'successful',
            default => 'failed',
        };
    }

    public function disburse(Transaction $transaction): void
    {
        if ($this->driver === 'local') {
            return;
        }

        if ($this->driver !== 'campay') {
            return;
        }

        try {
            $response = $this->campayRequest()
                ->post("{$this->campayBaseUrl}/api/withdraw/", [
                    'amount' => $this->formatAmount($transaction->amount),
                    'to' => $this->formatCameroonPhone($transaction->seller_phone),
                    'description' => "Agora payout for transaction #{$transaction->id}",
                    'external_reference' => (string) Str::uuid(),
                ])
                ->throw()
                ->json();
        } catch (ConnectionException|RequestException $exception) {
            if (!$this->campayFallbackToLocal) {
                throw $exception;
            }

            report($exception);

            return;
        }

        $transaction->update([
            'kpay_disburse_id' => $response['reference'] ?? null,
        ]);
    }

    public function markFailed(Transaction $transaction): void
    {
        $transaction->update(['status' => TransactionStatus::FAILED]);

        $this->notificationService->paymentFailed($transaction->buyer);
    }

    private function initiateCampayPayment(PurchaseRequest $purchaseRequest, array $data): ?string
    {
        $response = $this->campayRequest()
            ->post("{$this->campayBaseUrl}/api/collect/", [
                'amount' => $this->formatAmount($purchaseRequest->total_price),
                'currency' => 'XAF',
                'from' => $this->formatCameroonPhone($data['buyer_phone']),
                'description' => "Agora purchase request #{$purchaseRequest->id}",
                'external_reference' => (string) Str::uuid(),
                'external_user' => (string) $purchaseRequest->buyer_id,
            ])
            ->throw()
            ->json();

        return $response['reference'] ?? null;
    }

    private function campayRequest(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout($this->campayTimeout)
            ->connectTimeout($this->campayConnectTimeout)
            ->withHeaders([
                'Authorization' => 'Token '.$this->campayAccessToken(),
            ]);
    }

    private function campayAccessToken(): string
    {
        if ($this->campayToken) {
            return $this->campayToken;
        }

        return Cache::remember('campay:access_token', now()->addMinutes(50), function () {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout($this->campayTimeout)
                ->connectTimeout($this->campayConnectTimeout)
                ->post("{$this->campayBaseUrl}/api/token/", [
                    'username' => $this->campayUsername,
                    'password' => $this->campayPassword,
                ])
                ->throw()
                ->json();

            return $response['token'];
        });
    }

    private function formatAmount(mixed $amount): string
    {
        return (string) (int) round((float) $amount);
    }

    private function formatCameroonPhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($phone, '237')) {
            return $phone;
        }

        $phone = ltrim($phone, '0');

        return '237'.$phone;
    }
}
