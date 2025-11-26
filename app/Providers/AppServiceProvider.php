<?php

namespace App\Providers;

use App\Models\FootwearLoan;
use App\Models\User;
use App\Observers\FootwearLoanObserver;
use App\Observers\UserObserver;
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
        FootwearLoan::observe(FootwearLoanObserver::class);
        User::observe(UserObserver::class);
    }
}
