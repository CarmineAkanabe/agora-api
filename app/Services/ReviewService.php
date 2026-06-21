<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\User;
use Dotenv\Exception\ValidationException;

class ReviewService
{
    /**
     * Create a new class instance.
     */
    public function __construct()   {}

    public function store(User $reviewer, array $data): Review
    {
        $transaction = Transaction::findOrFail($data['transaction_id']);

        if ($transaction->buyer_id !== $reviewer->id) {
            throw ValidationException::withMessages([
                'transaction_id' => ['Only the buyer can leave a review.'],
            ]);
        }

        if ($transaction->status !== TransactionStatus::RELEASED) {
            throw ValidationException::withMessages([
                'transaction_id' => ['You can only review a completed transaction.'],
            ]);
        }

        if ($transaction->review()->exists()) {
            throw ValidationException::withMessages([
                'transaction_id' => ['You have already reviewed this transaction.'],
            ]);
        }

        return Review::create([
            'transaction_id' => $transaction->id,
            'reviewer_id'    => $reviewer->id,
            'reviewee_id'    => $transaction->seller_id,
            'rating'         => $data['rating'],
            'comment'        => $data['comment'] ?? null,
        ]);
    }

    public function sellerReviews(User $seller): array
    {
        $reviews = Review::where('reviewee_id', $seller->id)
            ->with('reviewer')
            ->latest()
            ->get();

        $average = $reviews->avg('rating');

        return [
            'average_rating' => round($average, 1),
            'total_reviews'  => $reviews->count(),
            'reviews'        => $reviews,
        ];
    }
}
