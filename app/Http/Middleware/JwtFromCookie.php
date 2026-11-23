<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Injects JWT from httpOnly cookie into Authorization header and authenticates the user
 * so auth()->user() and guest middleware work correctly.
 */
class JwtFromCookie
{
    public const COOKIE_NAME = 'auth_token';

    public function handle(Request $request, Closure $next): Response
    {
        // Admin area uses session-only auth; do not overwrite with JWT
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        $token = $request->cookie(self::COOKIE_NAME);

        if ($token && ! $request->header('Authorization')) {
            $request->headers->set('Authorization', 'Bearer ' . $token);

            try {
                $user = Auth::guard('jwt')->authenticate();
                if ($user) {
                    Auth::setUser($user);
                }
            } catch (\Throwable) {
                // Invalid or expired token — do not set user
            }
        }

        return $next($request);
    }
}
