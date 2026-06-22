<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestPolicy
{
    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->id === $purchaseRequest->buyer_id ||
               $user->id === $purchaseRequest->seller_id;
    }

    public function approve(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->id === $purchaseRequest->seller_id &&
               $purchaseRequest->status === RequestStatus::PENDING;
    }

    public function reject(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->id === $purchaseRequest->seller_id &&
               $purchaseRequest->status === RequestStatus::PENDING;
    }

    public function cancel(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->id === $purchaseRequest->buyer_id &&
               in_array($purchaseRequest->status, [
                   RequestStatus::PENDING,
                   RequestStatus::APPROVED,
               ]);
    }
}
