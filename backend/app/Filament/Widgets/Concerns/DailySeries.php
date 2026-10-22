<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/** Oxirgi N kun bo'yicha kunlik sonlar (PHP'da guruhlanadi: PostgreSQL va sqlite'da bir xil ishlaydi). */
trait DailySeries
{
    /** @return array<string, int> "Y-m-d" => son, eng eski kundan bugungacha */
    protected function dailySeries(Builder $query, int $days = 30, string $column = 'created_at'): array
    {
        $from = today()->subDays($days - 1);

        $counts = $query->clone()
            ->where($column, '>=', $from)
            ->pluck($column)
            ->countBy(fn ($date) => Carbon::parse($date)->toDateString());

        $series = [];
        for ($day = $from->copy(); $day->lte(today()); $day->addDay()) {
            $series[$day->toDateString()] = $counts[$day->toDateString()] ?? 0;
        }

        return $series;
    }
}
