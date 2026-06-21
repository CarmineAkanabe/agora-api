<?php

namespace App\Console\Commands;

use App\Enums\RequestStatus;
use App\Models\PurchaseRequest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('request:expire')]
#[Description('Expire approved purchase requests that passed the 2 hour payment window')]
class ExpireStaleRequestsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        PurchaseRequest::where('status', RequestStatus::APPROVED)
            ->where('expires_at', '<', now())
            ->update(['status' => RequestStatus::EXPIRED]);

        $this->info('Stale requests expired.');
    }
}
