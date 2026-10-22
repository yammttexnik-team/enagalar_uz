<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Marketing kampaniyasi. Bonus miqdorlari: null = standart (config/bonus.php), 0 = bonus yo'q.
 * Havola: {FRONTEND_URL}/kirish?c=KOD yoki https://t.me/BOT?start=c_KOD
 */
#[Fillable([
    'code', 'name', 'source', 'survey_bonus', 'listing_bonus', 'referral_bonus',
    'is_active', 'starts_at', 'ends_at', 'max_participants',
])]
class Campaign extends Model
{
    public const CODE_PATTERN = '/^[A-Z0-9_]{1,32}$/';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** Kodni tozalaydi (katta harf); yaroqsiz bo'lsa null. */
    public static function normalizeCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return preg_match(self::CODE_PATTERN, $code) ? $code : null;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function bonusTransactions(): HasMany
    {
        return $this->hasMany(BonusTransaction::class);
    }

    public function surveyResponses(): HasManyThrough
    {
        return $this->hasManyThrough(SurveyResponse::class, User::class);
    }

    /** Faol va sanalar oralig'ida (bonus berish mumkin). */
    public function isRunning(): bool
    {
        return $this->is_active
            && (! $this->starts_at || $this->starts_at->isPast())
            && (! $this->ends_at || $this->ends_at->isFuture());
    }

    /** Yangi ishtirokchi qo'shish mumkinmi (max_participants). */
    public function hasRoom(): bool
    {
        return $this->max_participants === null || $this->users()->count() < $this->max_participants;
    }

    public function scopeRunning(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function siteLink(): string
    {
        return rtrim(config('app.frontend_url'), '/').'/kirish?c='.$this->code;
    }

    public function botLink(): string
    {
        return 'https://t.me/'.config('services.telegram.bot_username').'?start=c_'.$this->code;
    }
}
