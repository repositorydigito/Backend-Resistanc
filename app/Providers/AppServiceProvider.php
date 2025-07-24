<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ProductVariant;
use App\Observers\ProductVariantObserver;
use App\Models\FootwearLoan;
use App\Observers\FootwearLoanObserver;

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
        // ProductVariant::observe(ProductVariantObserver::class);
        FootwearLoan::observe(FootwearLoanObserver::class);
    }
}
