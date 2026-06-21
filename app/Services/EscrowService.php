<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Notifications\EscrowReleasedNotification;
use App\Notifications\PaymentHeldNotification;

class EscrowService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected PickupCodeService $pickupCodeService, protected NotificationService $notificationService) {}

    public function hold(Transaction $transaction): void
    {
        $transaction->update([
            'status'          => TransactionStatus::HELD,
            'pickup_code'     => $this->pickupCodeService->generate(),
            'auto_release_at' => now()->addHours(48),
        ]);

        $transaction->purchaseRequest->update([
            'status' => RequestStatus::PAID,
        ]);

        $this->notificationService->paymentHeld($transaction->buyer, $transaction->seller);

        // $transaction->buyer->notify(new PaymentHeldNotification());
        // $transaction->seller->notify(new PaymentHeldNotification());
    }

    public function release(Transaction $transaction): void
    {
        $transaction->update([
            'status'              => TransactionStatus::RELEASED,
            'pickup_code_used_at' => now(),
        ]);

        $transaction->purchaseRequest->update([
            'status' => RequestStatus::COMPLETED,
        ]);

        $this->notificationService->escrowReleased($transaction->buyer, $transaction->seller);

        // $transaction->buyer->notify(new EscrowReleasedNotification());
        // $transaction->seller->notify(new EscrowReleasedNotification());
    }

    public function refund(Transaction $transaction): void
    {
        $transaction->update(['status' => TransactionStatus::REFUNDED]);

        $transaction->purchaseRequest->update([
            'status' => RequestStatus::CANCELLED,
        ]);
    }
}
