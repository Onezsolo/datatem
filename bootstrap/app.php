<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'jwt.cookie' => \App\Http\Middleware\JwtFromCookie::class,
            'admin.web' => \App\Http\Middleware\AdminWebGate::class,
            'web.auth' => \App\Http\Middleware\EnsureWebAuth::class,
            'feature' => \App\Http\Middleware\EnsureFeature::class,
            'developer' => \App\Http\Middleware\EnsureDeveloper::class,
            'developer.api' => \App\Http\Middleware\EnsureDeveloperApi::class,
        ]);
        $middleware->encryptCookies(except: [
            'auth_token',
        ]);
        $middleware->validateCsrfTokens(except: [
            'shop/webhooks/paystack',
        ]);
        $middleware->api(append: [
            'throttle:120,1',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\CheckMaintenanceMode::class,
            \App\Http\Middleware\JwtFromCookie::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
