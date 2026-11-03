<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Route middleware that returns 403 unless the signed-in user is an admin.
 */
class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return $next($request);
    }
}
