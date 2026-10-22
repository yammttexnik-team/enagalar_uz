<?php

use App\Http\Controllers\Api\Auth\TelegramAuthController;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/regions', [RegionController::class, 'index']);
    Route::get('/badges', [BadgeController::class, 'index']);

    Route::post('/telegram/webhook', TelegramWebhookController::class);

    Route::prefix('auth/telegram')->middleware('throttle:30,1')->group(function () {
        Route::post('/start', [TelegramAuthController::class, 'start']);
        Route::get('/status/{token}', [TelegramAuthController::class, 'status']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [TelegramAuthController::class, 'logout']);
        Route::get('/me', [MeController::class, 'show']);
        Route::post('/me/roles', [MeController::class, 'addRole']);
    });
});
