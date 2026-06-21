<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountBannedNotification;
use App\Notifications\DisputeRaisedNotification;
use App\Notifications\DisputeResolvedNotification;
use App\Notifications\EscrowReleasedNotification;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentHeldNotification;
use App\Notifications\PaymentInitiatedNotification;
use App\Notifications\PickupCodeVerifiedNotification;
use App\Notifications\PurchaseRequestApprovedNotification;
use App\Notifications\PurchaseRequestRejectedNotification;
use App\Notifications\VerificationApprovedNotification;
use App\Notifications\VerificationRejectedNotification;

class NotificationService
{
    public function verificationApproved(User $user): void
    {
        $user->notify(new VerificationApprovedNotification());
    }

    public function verificationRejected(User $user, ?string $reason): void
    {
        $user->notify(new VerificationRejectedNotification($reason));
    }

    public function requestApproved(User $buyer): void
    {
        $buyer->notify(new PurchaseRequestApprovedNotification());
    }

    public function requestRejected(User $buyer, ?string $reason): void
    {
        $buyer->notify(new PurchaseRequestRejectedNotification($reason));
    }

    public function paymentInitiated(User $buyer): void
    {
        $buyer->notify(new PaymentInitiatedNotification());
    }

    public function paymentHeld(User $buyer, User $seller): void
    {
        $buyer->notify(new PaymentHeldNotification());
        $seller->notify(new PaymentHeldNotification());
    }

    public function paymentFailed(User $buyer): void
    {
        $buyer->notify(new PaymentFailedNotification());
    }

    public function pickupCodeVerified(User $buyer, User $seller): void
    {
        $buyer->notify(new PickupCodeVerifiedNotification());
        $seller->notify(new PickupCodeVerifiedNotification());
    }

    public function escrowReleased(User $buyer, User $seller): void
    {
        $buyer->notify(new EscrowReleasedNotification());
        $seller->notify(new EscrowReleasedNotification());
    }

    public function disputeRaised(User $otherParty, string $reason): void
    {
        $otherParty->notify(new DisputeRaisedNotification($reason));
    }

    public function accountBanned(User $user): void
    {
        $user->notify(new AccountBannedNotification());
    }

    public function disputeResolved(User $buyer, User $seller, string $resolution): void
    {
        $buyer->notify(new DisputeResolvedNotification($resolution));
        $seller->notify(new DisputeResolvedNotification($resolution));
    }
}
