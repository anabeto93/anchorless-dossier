<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FileManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListFilesController extends Controller
{
    public function __construct(
        protected FileManagementService $fileManagementService
    ) {}
    
    public function __invoke(Request $request): JsonResponse
    {
        $apiResponse = $this->fileManagementService->listFilesGroupedByType($request->user()->id);
        return response()->json($apiResponse->toArray(), $apiResponse->errorCode);
    }
}
