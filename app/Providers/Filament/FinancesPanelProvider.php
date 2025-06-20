<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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

class FinancesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('finances')
            ->path('finances')
            ->login()
            ->spa()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->colors([
                'primary' => Color::Green,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Finance Management'),
                NavigationGroup::make()
                    ->label('Analytics'),
                NavigationGroup::make()
                    ->label('Settings'),
            ])
            ->discoverResources(in: app_path('Filament/Finances/Resources'), for: 'App\\Filament\\Finances\\Resources')
            ->discoverPages(in: app_path('Filament/Finances/Pages'), for: 'App\\Filament\\Finances\\Pages')
            ->pages([
                \App\Filament\Finances\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Finances/Widgets'), for: 'App\\Filament\\Finances\\Widgets')
            ->widgets([
                // Custom dashboard handles widgets
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
                \App\Http\Middleware\SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
