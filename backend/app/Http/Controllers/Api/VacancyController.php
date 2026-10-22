<?php

namespace App\Http\Controllers\Api;

use App\Enums\PriceUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchVacanciesRequest;
use App\Http\Resources\VacancyResource;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VacancyController extends Controller
{
    /** Faol va muddati o'tmagan e'lonlar bo'yicha qidiruv. */
    public function index(SearchVacanciesRequest $request): AnonymousResourceCollection
    {
        $f = $request->validated();

        $query = Vacancy::active()->withPublic()
            ->when($f['region'] ?? null, fn (Builder $q, $v) => $q->whereHas('region', fn ($r) => $r->where('slug', $v)))
            ->when($f['district'] ?? null, fn (Builder $q, $v) => $q->whereHas('district', fn ($r) => $r->where('slug', $v)))
            ->when($f['work_format'] ?? null, fn (Builder $q, $v) => $q->where('work_format', $v))
            ->when($f['language'] ?? null, fn (Builder $q, $v) => $q->whereJsonContains('languages', $v))
            // Maosh: yuqori chegara (yoki yagona qiymat) so'ralgan minimumdan kam bo'lmasin.
            ->when($f['salary_min'] ?? null, fn (Builder $q, $v) => $q
                ->where('salary_unit', $f['salary_unit'] ?? PriceUnit::Month->value)
                ->whereRaw('coalesce(salary_max, salary_min) >= ?', [$v]))
            ->when($request->boolean('live_in'), fn (Builder $q) => $q->where('live_in', true))
            ->when($request->boolean('is_urgent'), fn (Builder $q) => $q->where('is_urgent', true))
            ->when(isset($f['children_age']), fn (Builder $q) => $q->whereJsonContains('children_ages', (int) $f['children_age']));

        match ($f['sort'] ?? 'newest') {
            'salary_desc' => $query->orderByRaw('coalesce(salary_max, salary_min) desc'),
            default => $query->orderByDesc('published_at'),
        };

        return VacancyResource::collection($query->orderByDesc('id')->paginate(20)->withQueryString());
    }

    public function show(int $id): VacancyResource
    {
        $vacancy = Vacancy::active()->withPublic()->findOrFail($id);
        $vacancy->increment('views_count');

        return new VacancyResource($vacancy);
    }
}
