<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust Railway's load balancer so HTTPS is detected correctly
        $middleware->trustProxies(at: '*');

        // Force JSON Accept header on all /api/* requests, and start sessions
        // so guest cart resolution via session ID works without SPA cookie auth.
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        // Do not use statefulApi() — it adds CSRF verification to API routes
        // which breaks cross-origin SPA requests. Session-based guest cart still
        // works because StartSession is added above. Token auth is unaffected.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
