<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Paths that must always be reachable when maintenance mode is on.
     * - /maintenance: the maintenance page itself
     * - /admin: admin panel (admins need to reach settings to disable maintenance)
     * - /login, /signup, /forgot-password: authentication pages (admins need to log in)
     */
    private const ALLOWED_PREFIXES = ['maintenance', 'admin', 'login', 'signup', 'forgot-password'];

    /**
     * When maintenance mode is on in settings, redirect non-admin users to the maintenance page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! SettingsService::getBool('maintenance_mode')) {
            return $next($request);
        }

        $path = $request->path();

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $next($request);
            }
        }

        return redirect()->route('maintenance');
    }
}
