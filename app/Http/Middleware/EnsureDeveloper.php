<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeveloper
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login', ['redirect' => $request->fullUrl()]));
        }

        if (($user->role ?? null) !== 'developer') {
            abort(403, 'Developer access only.');
        }

        return $next($request);
    }
}
