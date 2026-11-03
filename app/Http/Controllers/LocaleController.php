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
        if ($redirect) {
            return redirect($redirect);
        }

        return back();
    }
}

