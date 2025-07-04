<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ApiResponse;
use App\DTOs\StoreFileMetadataDTO;
use App\Models\FileMetadata;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FileManagementService
{
    /**
     * Store file metadata in the database within a transaction.
     *
     * @param StoreFileMetadataDTO $dto
     * @return ApiResponse
     */
    public function storeMetadata(StoreFileMetadataDTO $dto): ApiResponse
    {
        $user = User::find($dto->userId);
        if (!$user) {
            return ApiResponse::declined(
                'User not found',
                400
            );
        }

        DB::beginTransaction();

        try {
            $fileMetadata = FileMetadata::create([
                'file_id' => $dto->fileId,
                'name' => $dto->name,
                'size' => $dto->size,
                'mime_type' => $dto->mimeType,
                'user_id' => $dto->userId
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            $errorCode = 500;
            $message = 'Failed to store file metadata';

            if ($e instanceof \PDOException && $e->getCode() === '23000') {
                $errorCode = 400;
                $message = 'Duplicate file ID or other constraint violation';
            }

            return ApiResponse::error(
                $message,
                $errorCode,
                config('app.debug') ? ['error' => $e->getMessage()] : []
            );
        }

        return ApiResponse::success(
            'File metadata stored successfully',
            201,
            ['file_id' => $fileMetadata->file_id]
        );
    }

    /**
     * Retrieve file metadata by file ID.
     *
     * @param string $fileId
     * @return ApiResponse
     */
    public function getMetadata(string $fileId): ApiResponse
    {
        $fileMetadata = FileMetadata::where('file_id', $fileId)->first();

        if (!$fileMetadata) {
            return ApiResponse::error(
                'File metadata not found',
                404,
                ['error' => 'The requested file metadata could not be found']
            );
        }

        return ApiResponse::success(
            'File metadata retrieved successfully',
            200,
            $fileMetadata->toArray()
        );
    }
}
