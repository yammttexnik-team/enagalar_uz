<?php

use App\Http\Controllers\Admin\ReviewFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(config('app.frontend_url')));

Route::get('/admin/review-file/{type}/{id}', ReviewFileController::class)
    ->middleware(['auth', 'signed'])
    ->name('admin.review-file');
