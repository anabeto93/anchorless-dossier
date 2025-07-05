<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ApiResponse;
use App\DTOs\StoreFileMetadataDTO;
use App\Jobs\ProcessFileUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    const MAX_FILE_SIZE = 4096; // 4MB in kilobytes
    
    /**
     * Uploads a file and queues it for processing.
     *
     * @param User $user
     * @param UploadedFile $file The uploaded file.
     * @param string $destination The destination directory for the file.
     * @return ApiResponse The response object.
     */
    public function upload(User $user, UploadedFile $file, string $destination): ApiResponse
    {
        // Validate file size
        if ($file->getSize() > self::MAX_FILE_SIZE * 1024) {
            return ApiResponse::declined(
                'File size exceeds maximum allowed',
                413
            );
        }

        $metadata = new StoreFileMetadataDTO(
            fileId: generateUniqueFileId($file->getClientOriginalName()),
            name: $file->getClientOriginalName(),
            size: $file->getSize(),
            mimeType: $file->getMimeType(),
            userId: $user->id,
            user: $user,
        );

        try {
            // First upload it to temporary local storage
            Storage::disk('local')->put('tmp/' . $metadata->fileId, $file->get());

            // Dispatch the job
            // $job = new ProcessFileUpload($metadata, $destination);
            // $jobId = Bus::dispatch($job);
            dispatch(new ProcessFileUpload($metadata, $destination))->delay(now()->addSeconds(3));

            $route = route('files.get', ['file' =>$metadata->fileId]);
            
            return ApiResponse::success(
                'File upload queued for processing',
                202,
                ['file_id' => $metadata->fileId, 'url' => $route]
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                'File upload failed',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }
}
