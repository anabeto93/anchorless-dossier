<?php

namespace App\Http\Controllers\Api;

use App\Models\FileMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\FileManagementService;

class PreviewFileController
{
    public function __construct(private FileManagementService $fileManagementService)
    {
    }
    public function __invoke(Request $request, string $fileId)
    {
        $result = $this->fileManagementService->getFileById($fileId);

        if (!$result->success) {
            return response()->json($result->toArray(), $result->errorCode);
        }

        /** @var FileMetadata $file */
        $file = $result->data['file'];

        $props = [
            'content-disposition' => 'inline',
            'content-type' => $file->mime_type,
        ];

        $content = Storage::disk($file->disk)->get($file->path);

        return response($content, 200, $props);
    }
}
