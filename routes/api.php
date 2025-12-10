<?php

use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('posts', PostController::class);
});
