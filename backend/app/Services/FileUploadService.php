<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ApiResponse;
use App\Jobs\ProcessFileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;

class FileUploadService
{
    const MAX_FILE_SIZE = 4096; // 4MB in kilobytes
    
    /**
     * Uploads a file and queues it for processing.
     *
     * @param UploadedFile $file The uploaded file.
     * @param string $destination The destination directory for the file.
     * @return ApiResponse The response object.
     */
    public function upload(UploadedFile $file, string $destination): ApiResponse
    {
        // Validate file size
        if ($file->getSize() > self::MAX_FILE_SIZE * 1024) {
            return ApiResponse::declined(
                'File size exceeds maximum allowed',
                413
            );
        }

        try {
            // Dispatch the job
            $job = new ProcessFileUpload(
                $file->get(),
                $file->getClientOriginalName(),
                $destination
            );
            $jobId = Bus::dispatch($job);
            
            return ApiResponse::success(
                'File upload queued for processing',
                202,
                ['job_id' => $jobId]
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
