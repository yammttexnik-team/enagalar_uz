<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Services\Bonus\BonusService;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'phone', 'telegram_id', 'telegram_username', 'email', 'password', 'avatar_path', 'locale', 'status', 'last_seen_at', 'campaign_id', 'referred_by_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /** Baza bilan bir xil standart qiymatlar (yangi modelda ham to'g'ri bo'lishi uchun). */
    protected $attributes = [
        'status' => 'active',
        'locale' => 'uz',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function nannyProfile(): HasOne
    {
        return $this->hasOne(NannyProfile::class);
    }

    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentProfile::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->using(UserBadge::class)
            ->withPivot(['granted_by', 'granted_at', 'expires_at'])
            ->withTimestamps();
    }

    /** Muddati o'tmagan nishonlar. */
    public function activeBadges(): BelongsToMany
    {
        return $this->badges()
            ->where('badges.is_active', true)
            ->where(fn ($q) => $q->whereNull('user_badges.expires_at')->orWhere('user_badges.expires_at', '>', now()));
    }

    public function identityVerifications(): HasMany
    {
        return $this->hasMany(IdentityVerification::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['last_read_message_id', 'muted', 'blocked_at'])
            ->withTimestamps();
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'target_user_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_id');
    }

    public function bonusTransactions(): HasMany
    {
        return $this->hasMany(BonusTransaction::class);
    }

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /** Joriy bonus balansi (muddati o'tmagan, so'm). */
    public function bonusBalance(): int
    {
        return app(BonusService::class)->balance($this);
    }

    /** Taklif kodi (8 belgi). Birinchi so'rovda yaratiladi. */
    public function referralCode(): string
    {
        if (! $this->referral_code) {
            do {
                $code = strtoupper(Str::random(8));
            } while (self::where('referral_code', $code)->exists());

            $this->forceFill(['referral_code' => $code])->saveQuietly();
        }

        return $this->referral_code;
    }

    public function referralLink(): string
    {
        return rtrim(config('app.frontend_url'), '/').'/kirish?r='.$this->referralCode();
    }

    public static function normalizeReferralCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return preg_match('/^[A-Z0-9]{8}$/', $code) ? $code : null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === UserStatus::Active
            && $this->hasAnyRole([Role::Admin->value, Role::Moderator->value]);
    }

    /** Ochiq API uchun ism: "Dilnoza K." (familiya faqat bosh harfi). */
    public function publicName(): string
    {
        $parts = preg_split('/\s+/u', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY);
        $first = $parts[0] ?? '';

        return count($parts) > 1 ? $first.' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.' : $first;
    }

    public function isVerified(): bool
    {
        return $this->activeBadges()->where('slug', Badge::VERIFIED)->exists();
    }
}
