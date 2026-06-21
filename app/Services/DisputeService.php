<?php

namespace App\Services;

use App\Enums\DisputeStatus;
use App\Enums\TransactionStatus;
use App\Models\Dispute;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\DisputeRaisedNotification;
use Dotenv\Exception\ValidationException;

class DisputeService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected NotificationService $notificationService)   {}

    public function store(User $user, array $data): Dispute
    {
        $transaction = Transaction::findOrFail($data['transaction_id']);

        if (
            $user->id !== $transaction->buyer_id &&
            $user->id !== $transaction->seller_id
        ) {
            throw ValidationException::withMessages([
                'transaction_id' => ['You are not part of this transaction.'],
            ]);
        }

        if ($transaction->status !== TransactionStatus::HELD) {
            throw ValidationException::withMessages([
                'transaction_id' => ['Disputes can only be raised on held transactions.'],
            ]);
        }

        if ($transaction->dispute()->exists()) {
            throw ValidationException::withMessages([
                'transaction_id' => ['A dispute already exists for this transaction.'],
            ]);
        }

        $dispute = Dispute::create([
            'transaction_id' => $transaction->id,
            'raised_by'      => $user->id,
            'reason'         => $data['reason'],
            'status'         => DisputeStatus::OPEN,
        ]);

        // Notify the other party
        $otherParty = $user->id === $transaction->buyer_id
            ? $transaction->seller
            : $transaction->buyer;

            $this->notificationService->disputeRaised($otherParty, $data['reason']);

        // $otherParty->notify(new DisputeRaisedNotification($data['reason']));

        return $dispute->load(['raisedBy', 'transaction']);
    }
}
