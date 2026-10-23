<?php

use App\Http\Controllers\Api\AiListingController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\Auth\TelegramAuthController;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\BonusController;
use App\Http\Controllers\Api\ChatFileController;
use App\Http\Controllers\Api\ConnectionController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ImportedListingController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\MyImportedClaimController;
use App\Http\Controllers\Api\MyNannyProfileController;
use App\Http\Controllers\Api\MyVacancyController;
use App\Http\Controllers\Api\MyVerificationController;
use App\Http\Controllers\Api\NannyController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\VacancyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/regions', [RegionController::class, 'index']);
    Route::get('/badges', [BadgeController::class, 'index']);
    Route::get('/skills', [SkillController::class, 'index']);
    Route::get('/promotions/prices', [PromotionController::class, 'prices']);

    // Ochiq qidiruv (ikki tomonlama): enagalar va e'lonlar
    Route::middleware('throttle:public-read')->group(function () {
        Route::get('/nannies', [NannyController::class, 'index']);
        Route::get('/nannies/{id}', [NannyController::class, 'show'])->whereNumber('id');
        Route::get('/nannies/{id}/reviews', [NannyController::class, 'reviews'])->whereNumber('id');
        Route::get('/vacancies', [VacancyController::class, 'index']);
        Route::get('/vacancies/{id}', [VacancyController::class, 'show'])->whereNumber('id');
        // Telegram kanallardan olingan e'lonlar (kontaktsiz, manbaga havola bilan)
        Route::get('/imported', [ImportedListingController::class, 'index']);
        Route::get('/imported/{id}', [ImportedListingController::class, 'show'])->whereNumber('id');
    });
    Route::post('/imported/{id}/removal-request', [ImportedListingController::class, 'removalRequest'])->whereNumber('id')->middleware('throttle:5,1');

    // Chat fayli: faqat imzolangan havola (<img>/<audio> uchun sarlavhasiz)
    Route::get('/chat-files/{message}', ChatFileController::class)->middleware('signed')->name('chat-files')->whereNumber('message');

    Route::post('/telegram/webhook', TelegramWebhookController::class);

    Route::prefix('auth/telegram')->middleware('throttle:30,1')->group(function () {
        Route::post('/start', [TelegramAuthController::class, 'start']);
        Route::get('/status/{token}', [TelegramAuthController::class, 'status']);
        Route::post('/status/{token}/confirm', [TelegramAuthController::class, 'confirm']);
    });

    Route::middleware(['auth:sanctum', 'not_blocked', 'last_seen'])->group(function () {
        Route::post('/auth/logout', [TelegramAuthController::class, 'logout']);
        Route::get('/me', [MeController::class, 'show']);
        Route::post('/me/roles', [MeController::class, 'addRole']);
        Route::get('/me/bonus', [BonusController::class, 'show']);

        // Kanallardan topilgan, kontakti foydalanuvchiga mos e'lonlar
        Route::get('/my/imported-claims', [MyImportedClaimController::class, 'index']);
        Route::post('/my/imported-claims/{id}/accept', [MyImportedClaimController::class, 'accept'])->whereNumber('id')->middleware('throttle:20,1');
        Route::post('/my/imported-claims/{id}/dismiss', [MyImportedClaimController::class, 'dismiss'])->whereNumber('id')->middleware('throttle:20,1');
        // Pullik xizmatlar (TOP, "Shoshilinch") — bonus bilan
        Route::post('/my/promotions', [PromotionController::class, 'store'])->middleware('throttle:20,1');

        Route::get('/surveys', [SurveyController::class, 'index']);
        Route::get('/surveys/{slug}', [SurveyController::class, 'show']);
        Route::post('/surveys/{slug}/responses', [SurveyController::class, 'store'])->middleware('throttle:10,1');

        // Tasdiqlash: pasport va hujjatlar (yopiq diskka)
        Route::get('/my/verification', [MyVerificationController::class, 'show']);
        Route::post('/my/verification/identity', [MyVerificationController::class, 'storeIdentity'])->middleware('throttle:20,1');
        // Admin tasdiqlagan ma'lumotni profilda ko'rsatish / yashirish
        Route::patch('/my/badges/{slug}', [MyVerificationController::class, 'updateBadge'])->middleware('throttle:30,1');

        // Ota-ona: e'lonlar va kelgan arizalar
        Route::middleware('has_role:parent')->group(function () {
            Route::get('/my/vacancies', [MyVacancyController::class, 'index']);
            Route::get('/my/vacancies/{vacancy}', [MyVacancyController::class, 'show'])->whereNumber('vacancy');
            Route::post('/my/vacancies', [MyVacancyController::class, 'store']);
            Route::put('/my/vacancies/{vacancy}', [MyVacancyController::class, 'update']);
            Route::delete('/my/vacancies/{vacancy}', [MyVacancyController::class, 'destroy']);
            Route::post('/my/vacancies/{vacancy}/close', [MyVacancyController::class, 'close']);
            Route::get('/my/vacancies/{vacancy}/applications', [ApplicationController::class, 'forVacancy']);
            Route::get('/my/vacancies/{vacancy}/matches', [MatchController::class, 'forVacancy']);
            Route::patch('/applications/{application}', [ApplicationController::class, 'updateStatus'])->middleware('throttle:applications');
        });

        // Enaga: anketa va arizalar
        Route::middleware('has_role:nanny')->group(function () {
            Route::get('/my/nanny-profile', [MyNannyProfileController::class, 'show']);
            Route::put('/my/nanny-profile', [MyNannyProfileController::class, 'update']);
            Route::post('/my/nanny-profile/submit', [MyNannyProfileController::class, 'submit']);
            Route::patch('/my/nanny-profile/availability', [MyNannyProfileController::class, 'availability']);
            Route::get('/my/nanny-profile/matches', [MatchController::class, 'forNanny']);
            Route::post('/vacancies/{id}/apply', [ApplicationController::class, 'apply'])->whereNumber('id')->middleware('throttle:applications');
            Route::get('/my/applications', [ApplicationController::class, 'mine']);
        });

        // AI: e'lon matnidan anketa/vakansiya maydonlarini to'ldirish (saqlamaydi; rol controllerda tekshiriladi)
        Route::post('/ai/parse-listing', [AiListingController::class, 'parse'])->middleware('throttle:10,1');

        // Chat
        Route::get('/chat/unread', [ConversationController::class, 'unread']);
        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::post('/conversations', [ConversationController::class, 'store'])->middleware('throttle:conversation-start');
        Route::prefix('/conversations/{conversation}')->whereNumber('conversation')->group(function () {
            Route::get('/', [ConversationController::class, 'show']);
            Route::get('/messages', [MessageController::class, 'index']);
            Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:30,1');
            Route::post('/read', [ConversationController::class, 'read']);
            Route::post('/mute', [ConversationController::class, 'mute']);
            Route::post('/block', [ConversationController::class, 'block'])->middleware('throttle:conversation-block');
            Route::delete('/block', [ConversationController::class, 'unblock'])->middleware('throttle:conversation-block');
            Route::post('/report', [ConversationController::class, 'report'])->middleware('throttle:conversation-report');
        });
        Route::patch('/messages/{message}', [MessageController::class, 'update'])->whereNumber('message');
        Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->whereNumber('message');

        // Bog'lanish va reyting: "Birga ishladingizmi?" va baholash
        Route::get('/my/connections', [ConnectionController::class, 'index']);
        Route::post('/connections/{connection}/answer', [ConnectionController::class, 'answer'])->whereNumber('connection')->middleware('throttle:20,1');
        Route::post('/connections/{connection}/review', [ConnectionController::class, 'review'])->whereNumber('connection')->middleware('throttle:10,1');

        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites', [FavoriteController::class, 'store'])->middleware('throttle:favorites');
        Route::delete('/favorites', [FavoriteController::class, 'destroy'])->middleware('throttle:favorites');
    });
});
