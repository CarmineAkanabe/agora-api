<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->buyer_id ||
               $user->id === $transaction->seller_id;
    }

    public function verifyCode(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->seller_id;
    }
}
