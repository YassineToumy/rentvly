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
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Postgres on Windows often returns CP1252 error strings.
        // Sanitize before JSON so the real DB error is visible (not "Malformed UTF-8").
        $exceptions->render(function (\Throwable $e, $request) {
            if (!($request->expectsJson() || $request->is('api/*'))) {
                return null;
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return null;
            }

            $message = $e->getMessage();
            if (!mb_check_encoding($message, 'UTF-8')) {
                $message = mb_convert_encoding($message, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');
            }

            // If Laravel failed while encoding another exception, unwrap a usable message
            if (str_contains($message, 'Malformed UTF-8') && $e->getPrevious()) {
                $prev = $e->getPrevious()->getMessage();
                if (!mb_check_encoding($prev, 'UTF-8')) {
                    $prev = mb_convert_encoding($prev, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');
                }
                $message = $prev;
            }

            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            $payload = [
                'success' => false,
                'message' => $message,
            ];
            if (config('app.debug')) {
                $payload['exception'] = class_basename($e);
            }

            return response()->json($payload, is_int($status) && $status >= 400 ? $status : 500);
        });
    })->create();
