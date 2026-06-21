<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
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
    }
}
