<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bonus:expire')->dailyAt('03:00');
Schedule::command('verification:prune')->dailyAt('03:30');
// Bog'langaniga 3 kun bo'lganlardan "Birga ishladingizmi?" (har biri bir marta)
Schedule::command('connections:ask')->hourlyAt(15);
