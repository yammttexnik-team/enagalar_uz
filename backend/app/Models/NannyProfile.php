<?php

namespace App\Models;

use App\Enums\NannyProfileStatus;
use App\Enums\PriceUnit;
use App\Enums\UserStatus;
use App\Models\Concerns\Promotable;
use App\Observers\NannyProfileObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'region_id', 'district_id', 'birth_year', 'bio', 'experience_years',
    'price_amount', 'price_unit', 'work_formats', 'languages', 'age_groups',
    'live_in', 'free_trial_day', 'is_available', 'status', 'published_at',
])]
#[ObservedBy(NannyProfileObserver::class)]
class NannyProfile extends Model
{
    use HasFactory, Promotable;

    protected function casts(): array
    {
        return [
            'price_unit' => PriceUnit::class,
            'status' => NannyProfileStatus::class,
            'work_formats' => 'array',
            'languages' => 'array',
            'age_groups' => 'array',
            'live_in' => 'boolean',
            'free_trial_day' => 'boolean',
            'is_available' => 'boolean',
            'rating_avg' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function scopeActive(Builder $query): void
    {
        // Bloklangan foydalanuvchining anketasi ko'rinmaydi.
        $query->where('status', NannyProfileStatus::Active)
            ->whereHas('user', fn ($u) => $u->where('status', UserStatus::Active));
    }

    /** Ochiq ko'rinish uchun kerakli bog'lamalar (N+1 bo'lmasligi uchun). */
    public function scopeWithPublic(Builder $query): void
    {
        $query->with(['user.activeBadges', 'region', 'district', 'skills']);
    }
}
