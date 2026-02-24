<?php

use App\Infrastructure\Media\Controllers\MediaController;
use App\Infrastructure\Media\Controllers\MediaUploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt', 'org.context'])->group(function () {
    Route::post('media', [MediaController::class, 'upload']);
    Route::get('media', [MediaController::class, 'list']);
    Route::delete('media/{id}', [MediaController::class, 'delete']);

    Route::post('media/uploads', [MediaUploadController::class, 'initiate']);
    Route::patch('media/uploads/{id}', [MediaUploadController::class, 'uploadChunk']);
    Route::get('media/uploads/{id}', [MediaUploadController::class, 'status']);
    Route::post('media/uploads/{id}/complete', [MediaUploadController::class, 'complete']);
    Route::delete('media/uploads/{id}', [MediaUploadController::class, 'abort']);
});
