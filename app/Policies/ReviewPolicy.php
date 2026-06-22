<?php

namespace App\Policies;

use App\Enums\TransactionStatus;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\User;

class ReviewPolicy
{
    public function create(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->buyer_id &&
               $transaction->status === TransactionStatus::RELEASED &&
               !$transaction->review()->exists();
    }
}
