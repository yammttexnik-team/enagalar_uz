<?php

use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\RegionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/regions', [RegionController::class, 'index']);
    Route::get('/badges', [BadgeController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', fn (Request $request) => $request->user()->load('activeBadges'));
    });
});
