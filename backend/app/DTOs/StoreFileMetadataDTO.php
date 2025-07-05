<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Http\Requests\UploadFileFormRequest;
use App\Models\User;

class StoreFileMetadataDTO
{
    public function __construct(
        public readonly string $fileId,
        public readonly string $name,
        public readonly int $size,
        public readonly string $mimeType,
        public readonly int $userId,
        public ?User $user = null,
        public ?string $disk = null,
        public ?string $path = null,
    ) {}

    public static function fromRequest(UploadFileFormRequest $request): self
    {
        return new self(
            fileId: generateUniqueFileId($request->file('file')->getClientOriginalName()),
            name: $request->file('file')->getClientOriginalName(),
            size: $request->file('file')->getSize(),
            mimeType: $request->file('file')->getMimeType(),
            userId: $request->user()->id,
            user: $request->user(),
        );
    }
}
