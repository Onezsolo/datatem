<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role === \App\Models\User::ROLE_ADMIN) {
            return $next($request);
        }

        // Check for JWT auth as well if it's an API request or uses 'api' guard
        if (auth('api')->check() && auth('api')->user()->role === \App\Models\User::ROLE_ADMIN) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return redirect('/dashboard')->with('error', 'You do not have administrative access.');
    }
}
