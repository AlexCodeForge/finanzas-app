<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // Use user's preferred language if set
            if ($user && isset($user->language) && $user->language) {
                app()->setLocale($user->language);
            } else {
                // Fallback to default locale
                app()->setLocale(config('app.locale', 'en'));
            }
        } else {
            // Check session for guest users or fallback to default
            $locale = session('locale', config('app.locale', 'en'));
            if (in_array($locale, ['en', 'es'])) {
                app()->setLocale($locale);
            } else {
                app()->setLocale('en');
            }
        }

        return $next($request);
    }
}
