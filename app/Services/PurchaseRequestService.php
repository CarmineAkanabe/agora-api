<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Models\Listing;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\PurchaseRequestApprovedNotification;
use App\Notifications\PurchaseRequestRejectedNotification;
use Dotenv\Exception\ValidationException;

class PurchaseRequestService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected NotificationService $notificationService)   {}

    public function store(User $buyer, array $data): PurchaseRequest
    {
        $listing = Listing::findOrFail($data['listing_id']);

        if ($listing->user_id === $buyer->id) {
            throw ValidationException::withMessages([
                'listing_id' => ['You cannot request to buy your own listing.'],
            ]);
        }

        if ($listing->status !== ListingStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'listing_id' => ['This listing is not available.'],
            ]);
        }

        if ($data['quantity'] > $listing->quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Requested quantity exceeds available stock.'],
            ]);
        }

        $existing = PurchaseRequest::where('listing_id', $listing->id)
            ->where('buyer_id', $buyer->id)
            ->whereIn('status', [
                RequestStatus::PENDING,
                RequestStatus::APPROVED,
            ])->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'listing_id' => ['You already have an active request for this listing.'],
            ]);
        }

        return PurchaseRequest::create([
            'listing_id'       => $listing->id,
            'buyer_id'         => $buyer->id,
            'seller_id'        => $listing->user_id,
            'quantity'         => $data['quantity'],
            'total_price'      => $listing->price * $data['quantity'],
            'meeting_location' => $data['meeting_location'],
            'whatsapp_number'  => $data['whatsapp_number'],
            'message'          => $data['message'] ?? null,
            'status'           => RequestStatus::PENDING,
        ]);
    }

    public function approve(PurchaseRequest $purchaseRequest, User $seller): PurchaseRequest
    {
        if ($purchaseRequest->seller_id !== $seller->id) {
            throw ValidationException::withMessages([
                'request' => ['You are not the seller of this listing.'],
            ]);
        }

        if ($purchaseRequest->status !== RequestStatus::PENDING) {
            throw ValidationException::withMessages([
                'request' => ['This request cannot be approved.'],
            ]);
        }

        $purchaseRequest->update([
            'status'     => RequestStatus::APPROVED,
            'expires_at' => now()->addHours(2),
        ]);

        $this->notificationService->requestApproved($purchaseRequest->buyer);

        // $purchaseRequest->buyer->notify(new PurchaseRequestApprovedNotification());

        return $purchaseRequest;
    }

    public function reject(PurchaseRequest $purchaseRequest, User $seller, ?string $reason): PurchaseRequest
    {
        if ($purchaseRequest->seller_id !== $seller->id) {
            throw ValidationException::withMessages([
                'request' => ['You are not the seller of this listing.'],
            ]);
        }

        if ($purchaseRequest->status !== RequestStatus::PENDING) {
            throw ValidationException::withMessages([
                'request' => ['This request cannot be rejected.'],
            ]);
        }

        $purchaseRequest->update(['status' => RequestStatus::REJECTED]);

        $this->notificationService->requestRejected($purchaseRequest->buyer, $reason);
        // $purchaseRequest->buyer->notify(
        //     new PurchaseRequestRejectedNotification($reason)
        // );

        return $purchaseRequest;
    }

    public function cancel(PurchaseRequest $purchaseRequest, User $buyer): PurchaseRequest
    {
        if ($purchaseRequest->buyer_id !== $buyer->id) {
            throw ValidationException::withMessages([
                'request' => ['You are not the buyer of this request.'],
            ]);
        }

        if (!in_array($purchaseRequest->status, [
            RequestStatus::PENDING,
            RequestStatus::APPROVED,
        ])) {
            throw ValidationException::withMessages([
                'request' => ['This request cannot be cancelled.'],
            ]);
        }

        $purchaseRequest->update(['status' => RequestStatus::CANCELLED]);

        return $purchaseRequest;
    }
}
