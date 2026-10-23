<?php

namespace App\Models;

use App\Enums\ImportedListingStatus;
use App\Enums\PriceUnit;
use App\Enums\WorkFormat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ochiq Telegram kanaldan olingan e'lon. Egasi hali tasdiqlamagan, saytda "manba: @kanal" bilan ko'rinadi.
 * Kontakt ochiq saqlanmaydi: `raw_text` shifrlangan, `contact_hashes` — faqat moslashtirish uchun HMAC.
 */
#[Fillable([
    'import_source_id', 'type', 'status', 'source_channel', 'source_post_id', 'source_url', 'source_posted_at',
    'raw_text', 'content_hash', 'title', 'description', 'region_id', 'district_id', 'work_format', 'schedule',
    'price_min', 'price_max', 'price_unit', 'children_count', 'children_ages', 'age_groups', 'languages',
    'experience_years', 'person_age', 'live_in', 'is_urgent', 'contact_hashes', 'published_at', 'expires_at',
    'last_checked_at', 'archived_at', 'archive_reason', 'removal_requested_at', 'removal_reason',
    'claimed_by_user_id', 'claimed_at', 'claim_dismissed_at',
])]
#[Hidden(['raw_text', 'contact_hashes', 'content_hash'])]
class ImportedListing extends Model
{
    public const TYPE_VACANCY = 'vacancy';

    public const TYPE_NANNY = 'nanny';

    /** Manbadagi e'lon necha kundan keyin saytdan avtomatik arxivga o'tadi. */
    public const LIFETIME_DAYS = 14;

    protected function casts(): array
    {
        return [
            'status' => ImportedListingStatus::class,
            'work_format' => WorkFormat::class,
            'price_unit' => PriceUnit::class,
            'raw_text' => 'encrypted',
            'children_ages' => 'array',
            'age_groups' => 'array',
            'languages' => 'array',
            'contact_hashes' => 'array',
            'live_in' => 'boolean',
            'is_urgent' => 'boolean',
            'source_posted_at' => 'datetime',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'archived_at' => 'datetime',
            'removal_requested_at' => 'datetime',
            'claimed_at' => 'datetime',
            'claim_dismissed_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class, 'import_source_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    /** Saytda ko'rinadiganlar: faol va muddati o'tmagan. */
    public function scopeVisible(Builder $query): void
    {
        $query->where('status', ImportedListingStatus::Active)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** Manbani qayta tekshirish kerak bo'lganlar (faol yoki tekshiruvda). */
    public function scopeWatched(Builder $query): void
    {
        $query->whereIn('status', [ImportedListingStatus::Active, ImportedListingStatus::Pending]);
    }

    /**
     * Foydalanuvchi egasi bo'lishi mumkin bo'lgan e'lonlar: kontakt xeshi mos, hali tasdiqlanmagan,
     * egasi "meniki emas" demagan. Xesh bo'lmasa — hech narsa.
     */
    public function scopeClaimableBy(Builder $query, User $user): void
    {
        $hashes = self::hashesForUser($user);

        $query->whereIn('status', [ImportedListingStatus::Active, ImportedListingStatus::Pending])
            ->whereNull('claimed_by_user_id')
            ->whereNull('claim_dismissed_at')
            ->where(function (Builder $q) use ($hashes) {
                if ($hashes === []) {
                    $q->whereRaw('1 = 0');

                    return;
                }
                foreach ($hashes as $hash) {
                    $q->orWhereJsonContains('contact_hashes', $hash);
                }
            });
    }

    /** Kontakt xeshlaridan biri foydalanuvchiniki bilan mos keladimi. */
    public function matchesUser(User $user): bool
    {
        return array_intersect(self::hashesForUser($user), $this->contact_hashes ?? []) !== [];
    }

    public function archive(string $reason): void
    {
        $this->update([
            'status' => ImportedListingStatus::Archived,
            'archived_at' => now(),
            'archive_reason' => $reason,
        ]);
    }

    /**
     * Kontaktni moslashtirish xeshi. Telefon: oxirgi 9 raqam (998 kodisiz), username: kichik harf, @ siz.
     * Bir xil kontakt har doim bir xil xesh beradi; ochiq ko'rinishga qaytarib bo'lmaydi.
     */
    public static function contactHash(string $contact): ?string
    {
        $contact = trim($contact);
        if ($contact === '') {
            return null;
        }

        if (preg_match('~^@?[A-Za-z][A-Za-z0-9_]{3,31}$~', $contact)) {
            $normalized = 'tg:'.mb_strtolower(ltrim($contact, '@'));
        } else {
            $digits = preg_replace('~\D~', '', $contact) ?? '';
            if (strlen($digits) < 9) {
                return null;
            }
            $normalized = 'ph:'.substr($digits, -9);
        }

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }

    /** Foydalanuvchining telefon va Telegram username xeshlari (egasini topish uchun). */
    public static function hashesForUser(User $user): array
    {
        return array_values(array_filter([
            $user->phone ? self::contactHash($user->phone) : null,
            $user->telegram_username ? self::contactHash('@'.$user->telegram_username) : null,
        ]));
    }
}
