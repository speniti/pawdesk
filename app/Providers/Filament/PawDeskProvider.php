<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\MagicLinkConfirmation;
use App\Filament\Pages\Auth\Profile;
use App\Filament\Pages\Auth\RegisterFirstAdmin;
use App\Filament\Pages\Tenancy\EditTenantSettings;
use App\Filament\Pages\Tenancy\RegisterTenant;
use App\Models\Tenant;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PawDeskProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('pawdesk')
            ->default()
            ->login(Login::class)
            ->registration(RegisterFirstAdmin::class)
            ->profile(Profile::class)
            ->colors([
                'primary' => Color::Amber,
                'pink' => Color::Pink,
            ])
            ->tenant(Tenant::class, slugAttribute: 'slug')
            ->tenantRegistration(RegisterTenant::class)
            ->tenantProfile(EditTenantSettings::class)
            ->routes(function (Panel $panel) {
                return Route::get('/auth/magic-link/{token}', MagicLinkConfirmation::class)
                    ->name('auth.magic-link.verify')
                    ->middleware('throttle:10,1');
            })
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
