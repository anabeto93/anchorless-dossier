<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ListFilesController;
use App\Http\Controllers\Api\UploadFileController;
use App\Http\Controllers\Api\DeleteFileController;
use App\Http\Controllers\Api\GetFileController;
use App\Http\Controllers\Api\StatusController;

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

Route::match(['get', 'post'], '/', StatusController::class)->name('api.status');

Route::group(['prefix' => 'files', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/', ListFilesController::class)->name('files.list');
    Route::post('/', UploadFileController::class)->name('files.upload');
    Route::delete('/{file}', DeleteFileController::class)->name('files.delete');
    Route::get('/{file}', GetFileController::class)->name('files.get');
});
