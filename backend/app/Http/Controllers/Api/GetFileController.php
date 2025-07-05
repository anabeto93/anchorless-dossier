<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FileManagementService;

class GetFileController extends Controller
{
    public function __construct(
        protected FileManagementService $fileManagementService
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $fileId)
    {
        $apiResponse = $this->fileManagementService->getMetadata($fileId, $request->user()->id);
        return response()->json($apiResponse->toArray(), $apiResponse->errorCode);
    }
}
