<?php

namespace App\Services;

use App\Models\Transaction;

class PickupCodeService
{
    /**
     * Create a new class instance.
     */
    public function __construct()   {}

    public function generate(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function verify(Transaction $transaction, string $code): bool
    {
        return $transaction->pickup_code === $code
            && is_null($transaction->pickup_code_used_at);
    }
}
