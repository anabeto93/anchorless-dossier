<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ListFilesController;
use App\Http\Controllers\Api\UploadFileController;
use App\Http\Controllers\Api\DeleteFileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/files', ListFilesController::class);
    Route::post('/files', UploadFileController::class);
    Route::delete('/files/{file}', DeleteFileController::class);
});
