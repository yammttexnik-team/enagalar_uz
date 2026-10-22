<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\Auth\TelegramAuthController;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MyNannyProfileController;
use App\Http\Controllers\Api\MyVacancyController;
use App\Http\Controllers\Api\MyVerificationController;
use App\Http\Controllers\Api\NannyController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\VacancyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/regions', [RegionController::class, 'index']);
    Route::get('/badges', [BadgeController::class, 'index']);
    Route::get('/skills', [SkillController::class, 'index']);

    // Ochiq qidiruv (ikki tomonlama): enagalar va e'lonlar
    Route::get('/nannies', [NannyController::class, 'index']);
    Route::get('/nannies/{id}', [NannyController::class, 'show'])->whereNumber('id');
    Route::get('/vacancies', [VacancyController::class, 'index']);
    Route::get('/vacancies/{id}', [VacancyController::class, 'show'])->whereNumber('id');

    Route::post('/telegram/webhook', TelegramWebhookController::class);

    Route::prefix('auth/telegram')->middleware('throttle:30,1')->group(function () {
        Route::post('/start', [TelegramAuthController::class, 'start']);
        Route::get('/status/{token}', [TelegramAuthController::class, 'status']);
    });

    Route::middleware(['auth:sanctum', 'last_seen'])->group(function () {
        Route::post('/auth/logout', [TelegramAuthController::class, 'logout']);
        Route::get('/me', [MeController::class, 'show']);
        Route::post('/me/roles', [MeController::class, 'addRole']);

        // Tasdiqlash: pasport va hujjatlar (yopiq diskka)
        Route::get('/my/verification', [MyVerificationController::class, 'show']);
        Route::post('/my/verification/identity', [MyVerificationController::class, 'storeIdentity'])->middleware('throttle:20,1');
        Route::post('/my/verification/documents', [MyVerificationController::class, 'storeDocument'])->middleware('throttle:20,1');
        Route::delete('/my/verification/documents/{document}', [MyVerificationController::class, 'destroyDocument'])->whereNumber('document');

        // Ota-ona: e'lonlar va kelgan arizalar
        Route::middleware('has_role:parent')->group(function () {
            Route::get('/my/vacancies', [MyVacancyController::class, 'index']);
            Route::post('/my/vacancies', [MyVacancyController::class, 'store']);
            Route::put('/my/vacancies/{vacancy}', [MyVacancyController::class, 'update']);
            Route::delete('/my/vacancies/{vacancy}', [MyVacancyController::class, 'destroy']);
            Route::post('/my/vacancies/{vacancy}/close', [MyVacancyController::class, 'close']);
            Route::get('/my/vacancies/{vacancy}/applications', [ApplicationController::class, 'forVacancy']);
            Route::get('/my/vacancies/{vacancy}/matches', [MatchController::class, 'forVacancy']);
            Route::patch('/applications/{application}', [ApplicationController::class, 'updateStatus']);
        });

        // Enaga: anketa va arizalar
        Route::middleware('has_role:nanny')->group(function () {
            Route::get('/my/nanny-profile', [MyNannyProfileController::class, 'show']);
            Route::put('/my/nanny-profile', [MyNannyProfileController::class, 'update']);
            Route::post('/my/nanny-profile/submit', [MyNannyProfileController::class, 'submit']);
            Route::patch('/my/nanny-profile/availability', [MyNannyProfileController::class, 'availability']);
            Route::get('/my/nanny-profile/matches', [MatchController::class, 'forNanny']);
            Route::post('/vacancies/{id}/apply', [ApplicationController::class, 'apply'])->whereNumber('id');
            Route::get('/my/applications', [ApplicationController::class, 'mine']);
        });

        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites', [FavoriteController::class, 'store']);
        Route::delete('/favorites', [FavoriteController::class, 'destroy']);
    });
});
