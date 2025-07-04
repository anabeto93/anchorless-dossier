<?php

declare(strict_types=1);

namespace App\DTOs;

class ApiResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly int $errorCode,
        public readonly string $message,
        public readonly array $data = [],
        public readonly array $errors = []
    ) {}

    public static function success(string $message, int $errorCode = 200, array $data = []): self
    {
        return new self(true, $errorCode, $message, $data);
    }

    public static function declined(string $message, int $errorCode = 400, array $errors = []): self
    {
        return new self(false, $errorCode, $message, [], $errors);
    }

    public static function error(string $message, int $errorCode = 500, array $errors = []): self
    {
        return new self(false, $errorCode, $message, [], $errors);
    }

    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'error_code' => $this->errorCode,
            'message' => $this->message,
        ];

        if (!empty($this->data)) {
            $result['data'] = $this->data;
        }

        if (!empty($this->errors)) {
            $result['errors'] = $this->errors;
        }

        return $result;
    }
}
