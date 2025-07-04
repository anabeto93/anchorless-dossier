<?php

declare(strict_types=1);

namespace App\DTOs;

class StoreFileMetadataDTO
{
    public function __construct(
        public readonly string $fileId,
        public readonly string $name,
        public readonly int $size,
        public readonly string $mimeType,
        public readonly int $userId
    ) {}
}
