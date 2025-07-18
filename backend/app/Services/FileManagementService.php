<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ApiResponse;
use App\DTOs\StoreFileMetadataDTO;
use App\Models\FileMetadata;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Jobs\DeleteFileJob;

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
        $user = $dto->user ?? User::find($dto->userId);
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
                'user_id' => $dto->userId,
                'disk' => $dto->disk,
                'path' => $dto->path,
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
     * @param int $userId
     * @return ApiResponse
     */
    public function getMetadata(string $fileId, int $userId): ApiResponse
    {
        $fileMetadata = FileMetadata::where('file_id', $fileId)->where('user_id', $userId)->first();

        if (!$fileMetadata) {
            return ApiResponse::error(
                'File metadata not found',
                404,
                ['error' => 'The requested file metadata could not be found']
            );
        }

        // Add the file path for frontend access
        $url = constructFileUrl($fileMetadata);

        return ApiResponse::success(
            'File metadata retrieved successfully',
            200,
            array_merge($fileMetadata->toArray(), [
                'path' => $url,
                'preview_url' => generateSignedFilePreviewUrl($fileMetadata->file_id)
            ])
        );
    }

    /**
     * Delete file metadata and dispatch job to delete physical file.
     *
     * @param string $fileId
     * @return ApiResponse
     */
    public function deleteMetadata(string $fileId): ApiResponse
    {
        $fileMetadata = FileMetadata::where('file_id', $fileId)->first();

        if ($fileMetadata) {
            $fileMetadata->delete();
        }

        // Always dispatch the file deletion job
        dispatch(new DeleteFileJob($fileId))->delay(now()->addSeconds(3));

        return ApiResponse::success(
            'File metadata deleted successfully',
        );
    }

    /**
     * List files grouped by type.
     *
     * @param int $userId
     * @return ApiResponse
     */
    public function listFilesGroupedByType(int $userId): ApiResponse
    {
        try {
            // Check if user exists
            if (!User::where('id', $userId)->exists()) {
                return ApiResponse::declined('User not found', 404);
            }

            // Paginate the files for the user
            $files = FileMetadata::where('user_id', $userId)
                ->paginate(15); // Default per page

            // since there are only 3 file types allowed, we need to map to string values
            $fileTypes = [
                'image/jpg' => 'JPG',
                'image/jpeg' => 'JPG',
                'application/pdf' => 'PDF',
                'image/png' => 'PNG',
            ];

            // Group the files by MIME type
            $groupedFiles = [];
            foreach ($files as $file) {
                $groupedFiles[$fileTypes[$file->mime_type]][] = [
                    'id' => $file->file_id,
                    'name' => $file->name,
                    'size' => $file->size,
                    'created_at' => $file->created_at,
                    'path' => constructFileUrl($file),
                    'preview_url' => generateSignedFilePreviewUrl($file->file_id)
                ];
            }

            // Build response data
            $data = [
                'grouped_files' => $groupedFiles,
                'pagination' => [
                    'current_page' => $files->currentPage(),
                    'per_page' => $files->perPage(),
                    'total' => $files->total(),
                    'last_page' => $files->lastPage(),
                ],
            ];
        } catch (\Throwable $e) {
            // Log the exception if needed
            $error = config('app.debug') ? ['error' => $e->getMessage()] : [];
            return ApiResponse::error('Failed to list files grouped by type', 500, $error);
        }

        return ApiResponse::success('Files listed successfully', 200, $data);
    }

    /**
     * Get file metadata by file ID.
     * 
     * @param string $fileId
     * @return ApiResponse
     */
    public function getFileById(string $fileId): ApiResponse
    {
        $fileMetadata = FileMetadata::where('file_id', $fileId)->first();

        if (!$fileMetadata) {
            return ApiResponse::error(
                'File not found',
                404,
                ['error' => 'The requested file could not be found']
            );
        }

        return ApiResponse::success(
            'File retrieved successfully',
            200,
            ['file' => $fileMetadata]
        );
    }
}
