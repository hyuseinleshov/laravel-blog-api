<?php

use App\Http\Controllers\Api\V1\ArticleBoostController;
use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle']);

    Route::middleware('throttle:5,1')->group(function () {
        Route::post('auth/register', [RegisterController::class, 'store']);
        Route::post('auth/login', [LoginController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', LogoutController::class);
        Route::get('auth/me', MeController::class);

        Route::post('subscriptions/checkout', [SubscriptionController::class, 'checkout']);
        Route::get('subscriptions/current', [SubscriptionController::class, 'current']);

        Route::post('articles/{article}/boost', ArticleBoostController::class);
        Route::apiResource('articles', ArticleController::class)->except(['index', 'show']);
        Route::apiResource('tags', TagController::class)->except(['index', 'show']);
    });

    Route::apiResource('articles', ArticleController::class)->only(['index', 'show']);
    Route::apiResource('tags', TagController::class)->only(['index', 'show']);
});
