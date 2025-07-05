<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $status = ApiResponse::success('Running', data: [
            'version' => '1.0.0',
            'status' => 'Running',
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
            'name' => config('app.name'),
        ]);

        return response()->json($status->toArray(), $status->errorCode);
    }
}
