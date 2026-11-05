<?php

namespace App\Services;

use App\Enums\ImportedListingStatus;
use App\Models\ImportedListing;
use App\Models\NannyProfile;
use App\Models\Region;
use App\Models\Vacancy;
use Illuminate\Support\Facades\Cache;

/**
 * Enaga narxlari statistikasi: Telegram e'lonlari, faol vakansiyalar va anketalardan avtomatik hisoblanadi.
 * Tomonlar: `nanny` — enaga so'raydi, `vacancy` — oila taklif qiladi.
 */
class PriceStats
{
    public const MIN_BUCKET = 3;

    public const WINDOW_DAYS = 90;

    public const CACHE_HOURS = 6;

    public const UNITS = ['month', 'day', 'hour'];

    /** Haqiqatga to'g'ri kelmaydigan (xato yoki boshqa narsa) narxlar chegarasi: [min, max] so'm. */
    public const BOUNDS = [
        'month' => [300_000, 30_000_000],
        'day' => [30_000, 1_500_000],
        'hour' => [5_000, 500_000],
    ];

    /** @return array<string, mixed> */
    public function get(?string $regionSlug = null): array
    {
        return Cache::remember('prices:v1:'.($regionSlug ?: 'all'), now()->addHours(self::CACHE_HOURS), fn () => $this->compute($regionSlug));
    }

    /** @return array<string, mixed> */
    public function compute(?string $regionSlug = null): array
    {
        $rows = $this->rows();
        $regionId = $regionSlug ? Region::where('slug', $regionSlug)->value('id') : null;
        $scoped = $regionId ? array_filter($rows, fn ($r) => $r['region_id'] === $regionId) : $rows;

        $units = [];
        foreach (self::UNITS as $unit) {
            $units[$unit] = [];
            foreach (['nanny', 'vacancy'] as $side) {
                $prices = array_column(array_filter($scoped, fn ($r) => $r['unit'] === $unit && $r['side'] === $side), 'price');
                $units[$unit][$side] = $this->bucket($prices, $unit);
            }
        }

        // Umumiy jadval uchun: viloyatlar bo'yicha oylik mediana (ikkala tomon birga), doim hamma viloyat bo'yicha.
        $regions = [];
        foreach (Region::orderBy('sort')->get(['id', 'slug']) as $region) {
            $prices = array_column(array_filter($rows, fn ($r) => $r['unit'] === 'month' && $r['region_id'] === $region->id), 'price');
            if (count($prices) >= self::MIN_BUCKET) {
                $regions[] = ['slug' => $region->slug, 'count' => count($prices), 'median' => $this->round($this->percentile($prices, 50), 'month')];
            }
        }
        usort($regions, fn ($a, $b) => $b['median'] <=> $a['median']);

        return [
            'region' => $regionId ? $regionSlug : null,
            'sample_size' => count($scoped),
            'window_days' => self::WINDOW_DAYS,
            'updated_at' => now()->toIso8601String(),
            'units' => $units,
            'regions' => $regions,
        ];
    }

    /**
     * Barcha manbalardan bir qatorga bitta narx.
     *
     * @return list<array{region_id: ?int, side: string, unit: string, price: float}>
     */
    private function rows(): array
    {
        $rows = [];
        $push = function (?int $regionId, string $side, $unit, $min, $max) use (&$rows) {
            $unit = $unit?->value ?? $unit;
            $price = $this->normalize($min, $max);
            if (! $unit || ! isset(self::BOUNDS[$unit]) || $price === null) {
                return;
            }
            [$lo, $hi] = self::BOUNDS[$unit];
            if ($price < $lo || $price > $hi) {
                return;
            }
            $rows[] = ['region_id' => $regionId, 'side' => $side, 'unit' => $unit, 'price' => $price];
        };

        ImportedListing::query()
            ->whereIn('status', [ImportedListingStatus::Active, ImportedListingStatus::Archived, ImportedListingStatus::Claimed])
            ->where('source_posted_at', '>=', now()->subDays(self::WINDOW_DAYS))
            ->get(['id', 'type', 'region_id', 'price_min', 'price_max', 'price_unit'])
            ->each(fn ($l) => $push($l->region_id, $l->type === ImportedListing::TYPE_NANNY ? 'nanny' : 'vacancy', $l->price_unit, $l->price_min, $l->price_max));

        Vacancy::active()->get(['id', 'region_id', 'salary_min', 'salary_max', 'salary_unit'])
            ->each(fn ($v) => $push($v->region_id, 'vacancy', $v->salary_unit, $v->salary_min, $v->salary_max));

        NannyProfile::active()->get(['id', 'region_id', 'price_amount', 'price_unit'])
            ->each(fn ($n) => $push($n->region_id, 'nanny', $n->price_unit, $n->price_amount, null));

        return $rows;
    }

    private function normalize(mixed $min, mixed $max): ?float
    {
        $min = $min ? (float) $min : null;
        $max = $max ? (float) $max : null;

        return match (true) {
            $min !== null && $max !== null => ($min + $max) / 2,
            default => $min ?? $max,
        };
    }

    /**
     * @param  list<float>  $prices
     * @return array<string, int>|null
     */
    private function bucket(array $prices, string $unit): ?array
    {
        if (count($prices) < self::MIN_BUCKET) {
            return null;
        }

        return [
            'count' => count($prices),
            'median' => $this->round($this->percentile($prices, 50), $unit),
            'p25' => $this->round($this->percentile($prices, 25), $unit),
            'p75' => $this->round($this->percentile($prices, 75), $unit),
            'min' => $this->round(min($prices), $unit),
            'max' => $this->round(max($prices), $unit),
        ];
    }

    /** Chiziqli interpolyatsiya bilan percentil. */
    private function percentile(array $values, float $p): float
    {
        sort($values);
        $pos = (count($values) - 1) * $p / 100;
        $lo = (int) floor($pos);
        $hi = (int) ceil($pos);

        return $values[$lo] + ($values[$hi] - $values[$lo]) * ($pos - $lo);
    }

    private function round(float $value, string $unit): int
    {
        $step = $unit === 'month' ? 10_000 : 1_000;

        return (int) (round($value / $step) * $step);
    }
}
