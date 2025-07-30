<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ProductVariant;
use App\Observers\ProductVariantObserver;
use App\Models\FootwearLoan;
use App\Models\User;
use App\Observers\FootwearLoanObserver;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;

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


        // Scramble::configure()
        //     ->withDocumentTransformers(function (OpenApi $openApi) {
        //         $openApi->secure(
        //             SecurityScheme::http('bearer')
        //         );
        //     });

        // Quien tiene acceso a nuestra documentacion del API
        // Gate::define('viewApiDocs', function (User $user) {
        //     return in_array($user->email, ['migelo5511@gmail.com']);
        // });
    }
}
