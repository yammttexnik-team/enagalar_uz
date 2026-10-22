<?php

namespace App\Models;

use App\Enums\LoginRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Saytdan Telegram bot orqali kirish so'rovi.
 * Sayt token yaratadi → foydalanuvchi botga /start login_<token> bilan o'tadi →
 * bot kontakt so'raydi → tasdiqlangach sayt token orqali Sanctum tokenini oladi.
 */
#[Fillable(['token', 'telegram_id', 'user_id', 'status', 'expires_at'])]
class TelegramLoginRequest extends Model
{
    public const START_PREFIX = 'login_';

    protected function casts(): array
    {
        return [
            'status' => LoginRequestStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public static function issue(): self
    {
        return self::create([
            'token' => Str::random(40),
            'expires_at' => now()->addMinutes(config('services.telegram.login_ttl_minutes')),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUsable(Builder $query): void
    {
        $query->where('status', LoginRequestStatus::Pending)->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function botUrl(): string
    {
        return 'https://t.me/'.config('services.telegram.bot_username').'?start='.self::START_PREFIX.$this->token;
    }

    public function approve(User $user): void
    {
        $this->update(['user_id' => $user->id, 'status' => LoginRequestStatus::Approved]);
    }
}
