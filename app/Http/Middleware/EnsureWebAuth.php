<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null) {
            $redirect = $request->isMethod('GET') ? $request->fullUrl() : url()->previous();

            return redirect()->guest(route('login', ['redirect' => $redirect]));
        }

        return $next($request);
    }
}
