<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\PurchaseRequest;
use App\Models\Transaction;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentInitiatedNotification;
use Http;

class PaymentService
{

    // Needed Variables
    protected string $baseUrl;
    protected string $secret;

    /**
     * Create a new class instance.
     */
    public function __construct(protected NotificationService $notificationService)
    {
        $this->baseUrl = config('services.kpay.base_url');
        $this->secret  = config('services.kpay.secret');
    }

    public function initiate(PurchaseRequest $purchaseRequest, array $data): Transaction
    {
        $sellerPhone = $purchaseRequest->seller->studentProfile->phone;

        // K-PAY collect call
        $response = Http::withToken($this->secret)
            ->post("{$this->baseUrl}/payments", [
                'amount'   => $purchaseRequest->total_price,
                'currency' => 'XAF',
                'provider' => $data['payment_method'],
                'customer' => $data['buyer_phone'],
            ]);

        // TODO: adjust field names once K-PAY docs are back online
        $kpayData = $response->json();

        $transaction = Transaction::create([
            'purchase_request_id' => $purchaseRequest->id,
            'buyer_id'            => $purchaseRequest->buyer_id,
            'seller_id'           => $purchaseRequest->seller_id,
            'amount'              => $purchaseRequest->total_price,
            'kpay_payment_id'     => $kpayData['id'] ?? null,
            'status'              => TransactionStatus::INITIATED,
            'payment_method'      => $data['payment_method'],
            'buyer_phone'         => $data['buyer_phone'],
            'seller_phone'        => $sellerPhone,
        ]);

        $this->notificationService->paymentInitiated($purchaseRequest->buyer);

        // $purchaseRequest->buyer->notify(new PaymentInitiatedNotification());

        return $transaction;
    }

    public function checkStatus(string $kpayPaymentId): string
    {
        $response = Http::withToken($this->secret)
            ->get("{$this->baseUrl}/payments/{$kpayPaymentId}");

        // TODO: adjust status field name once K-PAY docs are back online
        return $response->json()['status'] ?? 'pending';
    }

    public function disburse(Transaction $transaction): void
    {
        // TODO: confirm disburse endpoint from K-PAY docs
        $response = Http::withToken($this->secret)
            ->post("{$this->baseUrl}/disbursements", [
                'amount'    => $transaction->amount,
                'currency'  => 'XAF',
                'recipient' => $transaction->seller_phone,
            ]);

        $kpayData = $response->json();

        $transaction->update([
            'kpay_disburse_id' => $kpayData['id'] ?? null,
        ]);
    }

    public function markFailed(Transaction $transaction): void
    {
        $transaction->update(['status' => TransactionStatus::FAILED]);

        $this->notificationService->paymentFailed($transaction->buyer);
        // $transaction->buyer->notify(new PaymentFailedNotification());
    }
}
