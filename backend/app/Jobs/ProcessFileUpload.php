<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\StoreFileMetadataDTO;
use App\Services\FileManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public StoreFileMetadataDTO $metadata,
        public string $destination
    ) {}

    public function handle(FileManagementService $fileManagementService): void
    {
        $contents = Storage::disk('local')->get('tmp/' . $this->metadata->fileId);

        try {
            $fileManagementService->storeMetadata($this->metadata);
            Storage::disk(config('file.storage.disk'))->put($this->destination . '/' . $this->metadata->fileId, $contents);

            // now the file is stored, delete the temporary file
            Storage::disk('local')->delete('tmp/' . $this->metadata->fileId);

            $this->metadata->disk = config('file.storage.disk');
            $this->metadata->path = $this->destination . '/' . $this->metadata->fileId;

            // store the metadata in the database
            $fileManagementService->storeMetadata($this->metadata);
        } catch (\Throwable $e) {
            Log::debug('ProcessFileUpload::handle() Error uploading file', [
                'file_id' => $this->metadata->fileId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
