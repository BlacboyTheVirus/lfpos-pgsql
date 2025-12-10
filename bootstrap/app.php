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
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Illuminate\Database\QueryException $e, $request) {
            // Handle database connection errors gracefully
            if (str_contains($e->getMessage(), 'Connection refused') ||
                str_contains($e->getMessage(), 'Connection timed out') ||
                str_contains($e->getMessage(), 'SQLSTATE[HY000]') ||
                str_contains($e->getMessage(), 'SQLSTATE[08006]')) {

                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Service temporarily unavailable. Please try again later.',
                        'message' => 'We are experiencing technical difficulties.',
                    ], 503);
                }

                return response()->view('errors.503', [], 503);
            }
        });

        $exceptions->render(function (Illuminate\Database\ConnectionException $e, $request) {
            // Handle specific database connection exceptions
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Service temporarily unavailable. Please try again later.',
                    'message' => 'We are experiencing technical difficulties.',
                ], 503);
            }

            return response()->view('errors.503', [], 503);
        });
    })->create();
