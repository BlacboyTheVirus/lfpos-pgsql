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
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withBindings([
        // Disable policy auto-discovery by providing empty policy map
        \Illuminate\Auth\Access\Gate::class => function ($app) {
            $gate = new \Illuminate\Auth\Access\Gate($app, function () use ($app) {
                return $app['auth']->user();
            });

            // Register a global before callback that allows everything
            $gate->before(function ($user, $ability) {
                return true;
            });

            return $gate;
        },
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
