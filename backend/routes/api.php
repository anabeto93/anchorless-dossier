<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ListFilesController;
use App\Http\Controllers\Api\UploadFileController;
use App\Http\Controllers\Api\DeleteFileController;
use App\Http\Controllers\Api\GetFileController;

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
    Route::get('/files', ListFilesController::class)->name('files.list');
    Route::post('/files', UploadFileController::class)->name('files.upload');
    Route::delete('/files/{file}', DeleteFileController::class)->name('files.delete');
    Route::get('/files/{file}', GetFileController::class)->name('files.get');
});
