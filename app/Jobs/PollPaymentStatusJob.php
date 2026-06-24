<?php

namespace App\Jobs;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\EscrowService;
use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollPaymentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Transaction $transaction)   {}

    /**
     * Execute the job.
     */
    public function handle(PaymentService $paymentService, EscrowService $escrowService): void
    {
        $this->transaction->refresh();

        if ($this->transaction->status !== TransactionStatus::INITIATED) {
            return;
        }

        // Stop polling after 10 minutes
        if ($this->transaction->created_at->diffInMinutes(now()) > 10) {
            $paymentService->markFailed($this->transaction);
            return;
        }

        $status = $paymentService->checkStatus($this->transaction->kpay_payment_id);

        if ($status === 'pending') {
            self::dispatch($this->transaction)->delay(now()->addSeconds(15));
            return;
        }

        if ($status === 'successful') {
            $escrowService->hold($this->transaction);
            return;
        }

        $paymentService->markFailed($this->transaction);
    }
}
