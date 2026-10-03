<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',   // ← make sure this line exists
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Render terminates HTTPS at its proxy. Trust its forwarded headers so
        // Laravel generates HTTPS URLs and secure session cookies correctly.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'check.session' => \App\Http\Middleware\CheckSession::class,
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {

    })->create();

