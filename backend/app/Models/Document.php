<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use App\Models\Concerns\ReviewedByModerator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Nishon olish uchun hujjat: tibbiy ma'lumotnoma, dispanser ma'lumotnomasi, sertifikat.
 * Tasdiqlansa bog'langan nishon beriladi, fayl o'chiriladi.
 */
#[Fillable(['user_id', 'badge_id', 'type', 'path', 'issued_at', 'status'])]
class Document extends Model
{
    use ReviewedByModerator;

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'status' => VerificationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    protected function fileColumn(): string
    {
        return 'path';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    public function approve(User $moderator): void
    {
        DB::transaction(function () use ($moderator) {
            $this->markReviewed($moderator, VerificationStatus::Approved);
            $this->badge?->grantTo($this->user, $moderator);
        });
    }
}
