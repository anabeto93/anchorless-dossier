<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class ApiExceptionHandler
{
    public static array $handlers = [
        AuthenticationException::class => 'handleAuthenticationException',
        AccessDeniedHttpException::class => 'handleAuthorizationException',
        AuthorizationException::class => 'handleAuthorizationException',
        ValidationException::class => 'handleValidationException',
        ModelNotFoundException::class => 'handleNotFoundException',
        NotFoundHttpException::class => 'handleNotFoundException',
        MethodNotAllowedHttpException::class => 'handleMethodNotAllowedException',
        HttpException::class => 'handleHttpException',
        QueryException::class => 'handleQueryException',
        RouteNotFoundException::class => 'handleNotFoundException',
    ];

    public function handle(Request $request, Throwable $e): JsonResponse
    {
        $handler = $this->getHandler($e);

        if ($handler) {
            return $this->{$handler}($e, $request);
        }

        $this->logException($e, 'Unknown exception occurred');
        return response()->json([
            'success' => false,
            'error_code' => 500,
            'message' => 'An unknown error occurred.',
        ], 500);
    }

    private function getHandler(Throwable $e): ?string
    {
        $exceptionType = $this->getExceptionType($e);

        return self::$handlers[$exceptionType] ?? null;
    }

    public function handleAuthenticationException(
        AuthenticationException $e,
        Request $request
    ): JsonResponse {
        $this->logException($e, 'Authentication failed');
        return response()->json([
            'success' => false,
            'error_code' => 401,
            'message' => 'Authentication required. Please provide valid credentials.',
        ], 401);
    }



    public function handleAuthorizationException(
        AuthorizationException|AccessDeniedHttpException $e,
        Request $request
    ): JsonResponse {
        $this->logException($e, 'Authorization failed');
        return response()->json([
            'success' => false,
            'error_code' => 403,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }

    public function handleValidationException(
        ValidationException $e,
        Request $request
    ): JsonResponse {
        $errors = [];
        foreach ($e->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $errors[$field][] = $message;
            }
        }
        
        $this->logException($e, 'Validation failed', ['errors' => $errors]);
        return response()->json([
            'success' => false,
            'error_code' => 422,
            'message' => 'The provided data is invalid.',
            'errors' => $errors,
        ], 422);
    }

    public function handleNotFoundException(
        ModelNotFoundException|NotFoundHttpException|RouteNotFoundException $e,
        Request $request
    ): JsonResponse {
        $this->logException($e, 'Resource not found');
        $escapedUri = htmlspecialchars($request->getRequestUri(), ENT_QUOTES, 'UTF-8');
        $message = $e instanceof ModelNotFoundException 
            ? 'The requested resource was not found.' 
            : "The requested endpoint '{$escapedUri}' was not found.";
            
        return response()->json([
            'success' => false,
            'error_code' => 404,
            'message' => $message,
        ], 404);
    }

    public function handleMethodNotAllowedException(
        MethodNotAllowedHttpException $e,
        Request $request
    ): JsonResponse {
        $this->logException($e, 'Method not allowed');
        return response()->json([
            'success' => false,
            'error_code' => 405,
            'message' => "The {$request->method()} method is not allowed for this endpoint.",
        ], 405);
    }

    public function handleHttpException(HttpException $e, Request $request): JsonResponse
    {
        $this->logException($e, 'HTTP exception occurred');
        return response()->json([
            'success' => false,
            'error_code' => $e->getStatusCode(),
            'message' => $e->getMessage() ?: 'An HTTP error occurred.',
        ], $e->getStatusCode());
    }

    public function handleQueryException(QueryException $e, Request $request): JsonResponse
    {
        $this->logException($e, 'Database query failed', ['sql' => $e->getSql()]);
        return response()->json([
            'success' => false,
            'error_code' => 500,
            'message' => 'A database error occurred. Please try again later.',
        ], 500);
    }

    private function getExceptionType(Throwable $e): string
    {
        return basename(str_replace('\\', '/', get_class($e)));
    }

    private function logException(Throwable $e, string $message, array $context = []): void
    {
        $logContext = array_merge([
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
        ], $context);
        
        Log::warning($message, $logContext);
    }
}
