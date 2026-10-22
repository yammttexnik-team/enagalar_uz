<?php

namespace App\Filament\Widgets;

use App\Enums\NannyProfileStatus;
use App\Enums\VacancyStatus;
use App\Models\NannyProfile;
use App\Models\Region;
use App\Models\Vacancy;
use Filament\Widgets\ChartWidget;

/** Viloyatlar kesimida faol enagalar va vakansiyalar: qayerda talab, qayerda taklif ko'p. */
class RegionsChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Viloyatlar: taklif va talab';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $nannies = NannyProfile::where('status', NannyProfileStatus::Active)
            ->whereNotNull('region_id')->selectRaw('region_id, count(*) as total')->groupBy('region_id')->pluck('total', 'region_id');
        $vacancies = Vacancy::where('status', VacancyStatus::Active)
            ->selectRaw('region_id, count(*) as total')->groupBy('region_id')->pluck('total', 'region_id');

        $regions = Region::orderBy('sort')->get(['id', 'name_uz']);

        return [
            'datasets' => [
                [
                    'label' => 'Enagalar',
                    'data' => $regions->map(fn ($r) => (int) ($nannies[$r->id] ?? 0))->all(),
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => 'Vakansiyalar',
                    'data' => $regions->map(fn ($r) => (int) ($vacancies[$r->id] ?? 0))->all(),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $regions->pluck('name_uz')->map(fn ($n) => str_replace([' viloyati', ' Respublikasi'], '', $n))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['ticks' => ['precision' => 0]]]];
    }
}
