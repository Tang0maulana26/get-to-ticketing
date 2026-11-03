<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles switching the active locale and persisting it in session and cookie.
 */
class LocaleController extends Controller
{
    public function switch(Request $request, ?string $lang = null): RedirectResponse
    {
        $locale = $lang ?? $request->input('locale');

        if (! in_array($locale, ['en', 'id'], true)) {
            $locale = 'en';
        }

        session(['locale' => $locale]);
        cookie()->queue(cookie()->forever('locale', $locale));

        $redirect = $request->input('redirect') ?? $request->headers->get('referer');
        if (is_string($redirect) && $this->isSafeRedirectUrl($redirect, $request)) {
            return redirect()->to($redirect);
        }

        return to_route('catalog');
    }

    private function isSafeRedirectUrl(string $url, Request $request): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\')) {
            return true;
        }

        $parsed = parse_url($url);
        if (! is_array($parsed) || empty($parsed['host'])) {
            return false;
        }

        return strcasecmp($parsed['host'], $request->getHost()) === 0;
    }
}

