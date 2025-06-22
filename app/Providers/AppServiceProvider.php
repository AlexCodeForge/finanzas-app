<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Discord\Provider as DiscordProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

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
        // Register Discord Socialite Provider
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('discord', DiscordProvider::class);
        });
    }
}
