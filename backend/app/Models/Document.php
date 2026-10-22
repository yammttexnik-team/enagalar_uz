<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tibbiy ma'lumotnoma, sertifikat va boshqa nishon uchun hujjatlar. */
#[Fillable(['user_id', 'badge_id', 'type', 'path', 'issued_at', 'status'])]
class Document extends Model
{
    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'status' => VerificationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }
}
