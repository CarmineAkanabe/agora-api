<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Dispute;
use App\Models\Listing;
use App\Models\PurchaseRequest;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\User;
use App\Policies\DisputePolicy;
use App\Policies\ListingPolicy;
use App\Policies\PurchaseRequestPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\TransactionPolicy;
use Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Gate::define('isAdmin', fn(User $user) => $user->role === UserRole::ADMIN);

        Gate::define('isVerifiedStudent', fn(User $user) =>
            $user->role === UserRole::STUDENT &&
            $user->studentProfile?->verification_status === VerificationStatus::APPROVED &&
            !$user->is_banned
        );

        Gate::policy(Listing::class, ListingPolicy::class);
        Gate::policy(PurchaseRequest::class, PurchaseRequestPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(Dispute::class, DisputePolicy::class);
    }
}
