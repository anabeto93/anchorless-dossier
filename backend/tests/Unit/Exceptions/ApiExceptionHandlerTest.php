<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\ApiExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class ApiExceptionHandlerTest extends TestCase
{
    protected ApiExceptionHandler $handler;
    protected Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new ApiExceptionHandler();
        $this->request = Request::create('/test', 'GET');
    }

    #[Test]
    #[Group('exception_handlers')]
    public function test_handle_authentication_exception(): void
    {
        $exception = new AuthenticationException();
        $response = $this->handler->handleAuthenticationException($exception, $this->request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"success":false,"error_code":401,"message":"Authentication required. Please provide valid credentials."}',
            $response->getContent()
        );
    }

    #[Test]
    #[Group('exception_handlers')]
    public function test_handle_authorization_exception(): void
    {
        $exception = new AuthorizationException();
        $response = $this->handler->handleAuthorizationException($exception, $this->request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"success":false,"error_code":403,"message":"You do not have permission to perform this action."}',
            $response->getContent()
        );
    }

    #[Test]
    #[Group('exception_handlers')]
    public function test_handle_validation_exception(): void
    {
        $exception = ValidationException::withMessages(['email' => 'The email is invalid']);
        $response = $this->handler->handleValidationException($exception, $this->request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals(422, $content['error_code']);
        $this->assertEquals('The provided data is invalid.', $content['message']);
        $this->assertEquals([
            ['field' => 'email', 'message' => 'The email is invalid']
        ], $content['errors']);
    }

    #[Test]
    #[Group('exception_handlers')]
    public function test_handle_model_not_found_exception(): void
    {
        $exception = new ModelNotFoundException();
        $response = $this->handler->handleNotFoundException($exception, $this->request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"success":false,"error_code":404,"message":"The requested resource was not found."}',
            $response->getContent()
        );
    }

    #[Test]
    #[Group('exception_handlers')]
    public function test_handle_http_not_found_exception(): void
    {
        $exception = new NotFoundHttpException();
        $response = $this->handler->handleNotFoundException($exception, $this->request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString(
            'The requested endpoint \'\/test\' was not found.',
            $response->getContent()
        );
    }

    #[Test]
    #[Group('exception_handlers')]
    public function test_handle_method_not_allowed_exception(): void
    {
        $exception = new MethodNotAllowedHttpException(['GET'], 'Method not allowed');
        $response = $this->handler->handleMethodNotAllowedException($exception, $this->request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertStringContainsString(
            'The GET method is not allowed for this endpoint.',
            $response->getContent()
        );
    }

    #[Test]
    #[Group('exception_handlers')]
    public function test_handle_http_exception(): void
    {
        $exception = new HttpException(400, 'Bad Request');
        $response = $this->handler->handleHttpException($exception, $this->request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"success":false,"error_code":400,"message":"Bad Request"}',
            $response->getContent()
        );
    }

    #[Test]
    #[Group('exception_handlers')]
    public function test_handle_query_exception(): void
    {
        $exception = new QueryException(config('database.default'), 'sql', [], new \Exception('Database error'));
        $response = $this->handler->handleQueryException($exception, $this->request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"success":false,"error_code":500,"message":"A database error occurred. Please try again later."}',
            $response->getContent()
        );
    }

    #[Test]
    #[Group('exception_handlers')]
    public function test_handle_access_denied_http_exception(): void
    {
        $exception = new AccessDeniedHttpException();
        $response = $this->handler->handleAuthorizationException($exception, $this->request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"success":false,"error_code":403,"message":"You do not have permission to perform this action."}',
            $response->getContent()
        );
    }
}
