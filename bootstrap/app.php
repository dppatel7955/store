<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [
            'saffron_visitor_uuid',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\TrackVisitor::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/visitor/telemetry',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
