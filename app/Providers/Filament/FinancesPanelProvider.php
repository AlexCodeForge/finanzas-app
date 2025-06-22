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

use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Illuminate\Contracts\Auth\Authenticatable;

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
            ])
            ->plugin(
                FilamentSocialitePlugin::make()
                    // (required) Add providers corresponding with providers in `config/services.php`.
                    ->providers([
                        // Google OAuth provider
                        Provider::make('google')
                            ->label('Google')
                            ->icon('fab-google')
                            ->color(Color::hex('#4285f4'))
                            ->outlined(false)
                            ->stateless(false),

                        // Discord OAuth provider
                        Provider::make('discord')
                            ->label('Discord')
                            ->icon('fab-discord')
                            ->color(Color::hex('#5865f2'))
                            ->outlined(false)
                            ->stateless(false),
                    ])
                    // (optional) Override the panel slug to be used in the oauth routes. Defaults to the panel ID.
                    ->slug('finances')
                    // (optional) Enable/disable registration of new (socialite-) users.
                    ->registration(true)
                    // (optional) Change the associated model class.
                    ->userModelClass(\App\Models\User::class)
            );
    }
}
