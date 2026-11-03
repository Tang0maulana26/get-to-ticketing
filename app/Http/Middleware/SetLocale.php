<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the session or cookie locale, defaulting to English.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale')
            ?? $request->cookie('locale')
            ?? config('app.locale', 'en');

        if (! in_array($locale, ['en', 'id'], true)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}

