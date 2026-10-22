<?php

namespace App\Models;

use App\Enums\PriceUnit;
use App\Enums\VacancyStatus;
use App\Enums\WorkFormat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'parent_profile_id', 'region_id', 'district_id', 'title', 'description', 'work_format',
    'schedule', 'salary_min', 'salary_max', 'salary_unit', 'children_count', 'children_ages',
    'languages', 'live_in', 'is_urgent', 'starts_at', 'status', 'published_at', 'expires_at',
])]
class Vacancy extends Model
{
    use HasFactory, SoftDeletes;

    /** E'lon necha kundan keyin avtomatik tugaydi. */
    public const LIFETIME_DAYS = 30;

    protected function casts(): array
    {
        return [
            'work_format' => WorkFormat::class,
            'salary_unit' => PriceUnit::class,
            'status' => VacancyStatus::class,
            'children_ages' => 'array',
            'languages' => 'array',
            'live_in' => 'boolean',
            'is_urgent' => 'boolean',
            'starts_at' => 'date',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function parentProfile(): BelongsTo
    {
        return $this->belongsTo(ParentProfile::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', VacancyStatus::Active)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** Ochiq ko'rinish uchun kerakli bog'lamalar (N+1 bo'lmasligi uchun). */
    public function scopeWithPublic(Builder $query): void
    {
        $query->with(['region', 'district', 'parentProfile.user.activeBadges']);
    }

    public function publish(): void
    {
        $this->update([
            'status' => VacancyStatus::Active,
            'published_at' => now(),
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
        ]);
    }
}
