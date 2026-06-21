<?php

namespace App\Console\Commands;

use App\Enums\TransactionStatus;
use App\Jobs\DisbursePaymentJob;
use App\Models\Transaction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('escrow:release')]
#[Description('Auto release escrow for transactions past the 48 hour window')]
class AutoReleaseEscrowCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        Transaction::where('status', TransactionStatus::HELD)
            ->where('auto_release_at', '<', now())
            ->each(function (Transaction $transaction) {
                DisbursePaymentJob::dispatch($transaction);
            });

        $this->info('Escrow auto-release triggered.');
    }
}
