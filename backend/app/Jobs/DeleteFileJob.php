<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\FileMetadata;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class DeleteFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $fileId)
    {
    }

    public function handle(): void
    {
        // Get the file metadata
        $file = FileMetadata::where('file_id', $this->fileId)->first();
        if (!$file) {
            return;
        }

        // Delete the physical file
        Storage::disk('local')->delete($file->name);

        // Delete the metadata
        $file->delete();
    }
}
