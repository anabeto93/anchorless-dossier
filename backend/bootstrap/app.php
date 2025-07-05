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
        $exceptions->render(function (Throwable $e, $request) {
            $className = get_class($e);
            $handlers = \App\Exceptions\ApiExceptionHandler::$handlers;
            
            if (array_key_exists($className, $handlers)) {
                $method = $handlers[$className];
                $handler = new \App\Exceptions\ApiExceptionHandler();
                return $handler->$method($e, $request);
            }
            
            return response()->json([
                'success' => false,
                'error_code' => 500,
                'message' => 'An unexpected error occurred.',
            ], 500);
        });
    })->create();
