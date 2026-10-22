<?php

namespace App\Filament\Widgets;

use App\Models\NannyProfile;
use App\Models\User;
use App\Models\Vacancy;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/** Oxirgi 30 kun: yangi foydalanuvchilar, enaga anketalari va vakansiyalar. */
class SignupsChart extends ChartWidget
{
    use Concerns\DailySeries;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Oxirgi 30 kun';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $users = $this->dailySeries(User::query());

        return [
            'datasets' => [
                $this->dataset('Foydalanuvchilar', $users, '#10b981'),
                $this->dataset('Enaga anketalari', $this->dailySeries(NannyProfile::query()), '#f59e0b'),
                $this->dataset('Vakansiyalar', $this->dailySeries(Vacancy::withTrashed()), '#3b82f6'),
            ],
            'labels' => array_map(fn ($d) => Carbon::parse($d)->format('d.m'), array_keys($users)),
        ];
    }

    /** @param  array<string, int>  $series */
    private function dataset(string $label, array $series, string $color): array
    {
        return [
            'label' => $label,
            'data' => array_values($series),
            'borderColor' => $color,
            'backgroundColor' => $color,
            'tension' => 0.3,
        ];
    }
}
