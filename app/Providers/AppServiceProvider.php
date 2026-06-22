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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use RateLimiter;
use Illuminate\Http\Request;

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

        // General API limit
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please slow down.'
                    ], 429);
                });
        });

        // Payment initiation - strict
        RateLimiter::for('payment', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many payment attempts. Please wait before trying again.'
                    ], 429);
                });
        });

        // Listing creation
        RateLimiter::for('listing-create', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many listings created. Please wait before posting again.'
                    ], 429);
                });
        });

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
