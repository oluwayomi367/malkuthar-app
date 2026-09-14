<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * §60-§68 — réserve une route à un compte role=admin (§69 - permissions
 * par rôle). À utiliser en plus de `auth`, jamais seul.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Cette action est réservée aux administrateurs MALKUTHAR.');

        return $next($request);
    }
}
