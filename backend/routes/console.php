<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Buyruq natijasi storage/logs/schedule-YYYY-MM-DD.log ga yoziladi (storage — doimiy volume,
 * deploy'da konteyner qayta yaratilsa ham saqlanadi; docker logs esa o'chadi).
 */
$logged = function (Event $event, string $name): Event {
    $path = storage_path('logs/schedule-'.now('Asia/Tashkent')->format('Y-m-d').'.log');

    return $event
        ->before(fn () => File::append($path, "\n[".now('Asia/Tashkent')->format('Y-m-d H:i:s')."] {$name}\n"))
        ->appendOutputTo($path)
        ->onFailure(fn () => File::append($path, '['.now('Asia/Tashkent')->format('H:i:s')."] {$name}: XATO\n"));
};

$logged(Schedule::command('bonus:expire')->dailyAt('03:00'), 'bonus:expire');
$logged(Schedule::command('verification:prune')->dailyAt('03:30'), 'verification:prune');
// Bog'langaniga 3 kun bo'lganlardan "Birga ishladingizmi?" (har biri bir marta)
$logged(Schedule::command('connections:ask')->hourlyAt(15), 'connections:ask');
// Ochiq Telegram kanallardan e'lonlar: 08:00–22:00 har 2 soatda, manbadagi holat kuniga ikki marta
$logged(Schedule::command('imports:fetch')->cron('0 8-22/2 * * *')->timezone('Asia/Tashkent')->withoutOverlapping(120), 'imports:fetch');
$logged(Schedule::command('imports:check')->cron('30 9,18 * * *')->timezone('Asia/Tashkent')->withoutOverlapping(120), 'imports:check');
$logged(Schedule::command('channel:digest')->dailyAt('19:00')->timezone('Asia/Tashkent')->withoutOverlapping(), 'channel:digest');

// 30 kundan eski schedule-*.log fayllarini o'chirish (laravel-*.log ni daily kanalning o'zi tozalaydi)
Schedule::call(function () {
    foreach (File::glob(storage_path('logs/schedule-*.log')) as $file) {
        if (File::lastModified($file) < now()->subDays(30)->getTimestamp()) {
            File::delete($file);
        }
    }
})->dailyAt('04:00')->name('schedule-logs:prune');
