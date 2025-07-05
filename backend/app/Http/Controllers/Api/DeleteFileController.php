<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FileManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteFileController extends Controller
{
    public function __construct(
        protected FileManagementService $fileManagementService
    ) {}
    
    public function __invoke(Request $request, string|int $id): JsonResponse
    {
        $apiResponse = $this->fileManagementService->deleteMetadata("{$id}", (string) $request->user()->id);
        return response()->json($apiResponse->toArray(), $apiResponse->errorCode);
    }
}
