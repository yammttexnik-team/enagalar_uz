<?php

namespace App\Models;

use App\Enums\LoginRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Saytdan Telegram bot orqali kirish so'rovi.
 * Sayt token yaratadi → foydalanuvchi botga /start login_<token> bilan o'tadi →
 * bot kontakt so'raydi → tasdiqlangach bot 6 xonali kod yuboradi →
 * foydalanuvchi kodni saytga kiritadi va sayt Sanctum tokenini oladi.
 * Kod bo'lmasa, havolani boshqa odamga yuborib uning akkauntiga kirib olish mumkin edi.
 */
#[Fillable(['token', 'telegram_id', 'user_id', 'status', 'code_hash', 'attempts', 'expires_at', 'campaign_code', 'referral_code'])]
#[Hidden(['code_hash'])]
class TelegramLoginRequest extends Model
{
    public const START_PREFIX = 'login_';

    public const MAX_ATTEMPTS = 5;

    protected function casts(): array
    {
        return [
            'status' => LoginRequestStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public static function issue(?string $campaignCode = null, ?string $referralCode = null): self
    {
        return self::create([
            'campaign_code' => Campaign::normalizeCode($campaignCode),
            'referral_code' => User::normalizeReferralCode($referralCode),
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

    /** Foydalanuvchini bog'laydi va botda ko'rsatiladigan bir martalik kodni qaytaradi (bazada faqat xeshi). */
    public function approve(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'user_id' => $user->id,
            'status' => LoginRequestStatus::Approved,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
        ]);

        return $code;
    }

    public function attemptsExhausted(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    /** Kodni tekshiradi; noto'g'ri bo'lsa urinishlar sonini oshiradi. */
    public function checkCode(string $code): bool
    {
        if ($this->code_hash && Hash::check($code, $this->code_hash)) {
            return true;
        }

        self::whereKey($this->id)->where('attempts', '<', self::MAX_ATTEMPTS)->increment('attempts');
        $this->refresh();

        return false;
    }

    /** So'rovni bir marta ishlatilgan deb belgilaydi; parallel so'rovlardan faqat bittasi muvaffaqiyatli bo'ladi. */
    public function consume(): bool
    {
        return self::whereKey($this->id)
            ->where('status', LoginRequestStatus::Approved)
            ->update(['status' => LoginRequestStatus::Consumed]) === 1;
    }
}
