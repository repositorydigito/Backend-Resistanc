<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use App\Filament\Pages\Auth\Login;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class WebPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('web')
            ->path('web')
            ->login(Login::class)
            ->brandLogo(asset('image/logos/resistance-logo-two.png'))
            ->darkModeBrandLogo(asset('image/logos/resistance-logo-two-white.png'))
            ->colors([
                'primary' => Color::hex('#B0694C'),
            ])
            ->discoverResources(in: app_path('Filament/Web/Resources'), for: 'App\\Filament\\Web\\Resources')
            ->resources([
                \App\Filament\Web\Resources\HomePageContentResource::class,
                \App\Filament\Web\Resources\FaqResource::class,
                \App\Filament\Web\Resources\LegalPolicyResource::class,
                \App\Filament\Web\Resources\LegalFaqResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Web/Pages'), for: 'App\\Filament\\Web\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Web/Widgets'), for: 'App\\Filament\\Web\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
