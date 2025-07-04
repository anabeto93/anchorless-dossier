<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\DTOs\ApiResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('dto')]
class ApiResponseTest extends TestCase
{
    #[Test]
    public function it_can_create_a_successful_response(): void
    {
        $response = new ApiResponse(
            success: true,
            errorCode: 200,
            message: 'Operation successful',
            data: ['id' => 1]
        );

        $this->assertTrue($response->success);
        $this->assertEquals(200, $response->errorCode);
        $this->assertEquals('Operation successful', $response->message);
        $this->assertEquals(['id' => 1], $response->data);
        $this->assertEmpty($response->errors);
    }

    #[Test]
    public function it_can_create_a_declined_response(): void
    {
        $response = new ApiResponse(
            success: false,
            errorCode: 422,
            message: 'Validation failed',
            errors: ['name' => ['The name field is required.']]
        );

        $this->assertFalse($response->success);
        $this->assertEquals(422, $response->errorCode);
        $this->assertEquals('Validation failed', $response->message);
        $this->assertEmpty($response->data);
        $this->assertEquals(['name' => ['The name field is required.']], $response->errors);
    }

    #[Test]
    public function it_can_create_an_error_response(): void
    {
        $response = new ApiResponse(
            success: false,
            errorCode: 500,
            message: 'Internal Server Error',
            errors: ['detail' => 'Something went wrong.']
        );

        $this->assertFalse($response->success);
        $this->assertEquals(500, $response->errorCode);
        $this->assertEquals('Internal Server Error', $response->message);
        $this->assertEmpty($response->data);
        $this->assertEquals(['detail' => 'Something went wrong.'], $response->errors);
    }

    #[Test]
    public function it_has_default_empty_arrays_for_data_and_errors(): void
    {
        $response = new ApiResponse(
            success: true,
            errorCode: 200,
            message: 'Success'
        );

        $this->assertEmpty($response->data);
        $this->assertEmpty($response->errors);
    }

    #[Test]
    public function it_can_create_a_success_response_via_factory_method(): void
    {
        $response = ApiResponse::success('Operation successful', 200, ['id' => 1]);

        $this->assertTrue($response->success);
        $this->assertEquals(200, $response->errorCode);
        $this->assertEquals('Operation successful', $response->message);
        $this->assertEquals(['id' => 1], $response->data);
        $this->assertEmpty($response->errors);
    }

    #[Test]
    public function it_can_create_a_declined_response_via_factory_method(): void
    {
        $response = ApiResponse::declined('Validation failed', 422, ['email' => ['Invalid email']]);

        $this->assertFalse($response->success);
        $this->assertEquals(422, $response->errorCode);
        $this->assertEquals('Validation failed', $response->message);
        $this->assertEmpty($response->data);
        $this->assertEquals(['email' => ['Invalid email']], $response->errors);
    }

    #[Test]
    public function it_can_create_an_error_response_via_factory_method(): void
    {
        $response = ApiResponse::error('Server error', 500, ['detail' => 'Internal server error']);

        $this->assertFalse($response->success);
        $this->assertEquals(500, $response->errorCode);
        $this->assertEquals('Server error', $response->message);
        $this->assertEmpty($response->data);
        $this->assertEquals(['detail' => 'Internal server error'], $response->errors);
    }

    #[Test]
    public function it_converts_to_array_correctly(): void
    {
        $response = new ApiResponse(
            success: true,
            errorCode: 200,
            message: 'Success',
            data: ['user' => ['name' => 'John']],
            errors: []
        );

        $this->assertEquals([
            'success' => true,
            'error_code' => 200,
            'message' => 'Success',
            'data' => ['user' => ['name' => 'John']],
        ], $response->toArray());
    }

    #[Test]
    public function it_converts_error_response_to_array_correctly(): void
    {
        $response = new ApiResponse(
            success: false,
            errorCode: 400,
            message: 'Bad Request',
            data: [],
            errors: ['input' => ['Invalid']]
        );

        $this->assertEquals([
            'success' => false,
            'error_code' => 400,
            'message' => 'Bad Request',
            'errors' => ['input' => ['Invalid']],
        ], $response->toArray());
    }

    #[Test]
    public function it_converts_to_array_with_empty_data_and_errors(): void
    {
        $response = new ApiResponse(
            success: true,
            errorCode: 200,
            message: 'Success'
        );

        $this->assertEquals([
            'success' => true,
            'error_code' => 200,
            'message' => 'Success',
        ], $response->toArray());
    }
}
