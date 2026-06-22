<?php

namespace App\Policies;

use App\Enums\TransactionStatus;
use App\Models\Dispute;
use App\Models\Transaction;
use App\Models\User;

class DisputePolicy
{
    public function create(User $user, Transaction $transaction): bool
    {
        return (
            $user->id === $transaction->buyer_id ||
            $user->id === $transaction->seller_id
        ) &&
        $transaction->status === TransactionStatus::HELD &&
        !$transaction->dispute()->exists();
    }

    public function view(User $user, Dispute $dispute): bool
    {
        return $user->id === $dispute->transaction->buyer_id ||
               $user->id === $dispute->transaction->seller_id;
    }
}
