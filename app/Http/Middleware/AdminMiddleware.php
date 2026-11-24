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
        $sessionUser = auth()->user();
        $apiUser = auth('api')->user();

        if ($sessionUser?->isDeveloper() && \App\Services\HandoverService::isCompleted()) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Developer access ended after handover.'], 403)
                : redirect('/dashboard')->with('error', 'Developer access ended after handover.');
        }

        if ($apiUser?->isDeveloper() && \App\Services\HandoverService::isCompleted()) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Developer access ended after handover.'], 403)
                : redirect('/dashboard')->with('error', 'Developer access ended after handover.');
        }
        if (auth()->check() && in_array(auth()->user()->role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_DEVELOPER], true)) {
            return $next($request);
        }

        // Check for JWT auth as well if it's an API request or uses 'api' guard
        if (auth('api')->check() && in_array(auth('api')->user()->role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_DEVELOPER], true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return redirect('/dashboard')->with('error', 'You do not have administrative access.');
    }
}
