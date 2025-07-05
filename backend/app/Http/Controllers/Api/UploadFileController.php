<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFileFormRequest;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;

class UploadFileController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService)
    {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(UploadFileFormRequest $request): JsonResponse
    {
        $result = $this->fileUploadService->upload($request->user(), $request->file('file'), config('file.storage.path'));

        return response()->json($result->toArray(), $result->errorCode);
    }
}
