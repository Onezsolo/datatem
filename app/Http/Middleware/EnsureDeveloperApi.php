<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeveloperApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user() ?? $request->user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (! $user->isDeveloper()) {
            return response()->json(['error' => 'Developer access only.'], 403);
        }

        return $next($request);
    }
}
