<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        // `npm run dev` serves assets from another origin (e.g. http://[::1]:5173); allow it locally only.
        $vite = '';
        $viteSocket = '';
        if (app()->environment('local') && Vite::isRunningHot()) {
            $vite = ' '.rtrim(trim(file_get_contents(Vite::hotFile())), '/');
            $viteSocket = ' '.preg_replace('/^http/', 'ws', trim($vite));
        }

        return "default-src 'self'; img-src 'self' data:{$vite}; style-src 'self' 'unsafe-inline'{$vite}; "
            ."script-src 'self'{$vite}; font-src 'self'{$vite}; connect-src 'self'{$vite}{$viteSocket}; "
            ."object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'";
    }
}
