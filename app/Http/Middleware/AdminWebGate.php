<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminWebGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login', ['redirect' => $request->fullUrl()]));
        }

        if (! in_array($user->role ?? null, ['admin', 'developer'], true)) {
            abort(403, 'Admin access only.');
        }

        return $next($request);
    }
}
