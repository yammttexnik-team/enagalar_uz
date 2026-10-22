<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use App\Models\Concerns\ReviewedByModerator;
use App\Services\Bonus\VerificationRewards;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Pasport orqali shaxsni tasdiqlash so'rovi.
 * Shaxsiy maydonlar bazada shifrlangan holda saqlanadi, pasport rasmi tekshiruvdan keyin o'chiriladi.
 */
#[Fillable(['user_id', 'full_name', 'passport_series', 'passport_number', 'pinfl', 'birth_date', 'document_path', 'status'])]
#[Hidden(['passport_series', 'passport_number', 'pinfl', 'birth_date', 'document_path'])]
class IdentityVerification extends Model
{
    use ReviewedByModerator;

    protected function casts(): array
    {
        return [
            'full_name' => 'encrypted',
            'passport_series' => 'encrypted',
            'passport_number' => 'encrypted',
            'pinfl' => 'encrypted',
            'birth_date' => 'encrypted',
            'status' => VerificationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    protected function fileColumn(): string
    {
        return 'document_path';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approve(User $moderator): void
    {
        DB::transaction(function () use ($moderator) {
            $this->markReviewed($moderator, VerificationStatus::Approved);
            Badge::where('slug', Badge::VERIFIED)->firstOrFail()->grantTo($this->user, $moderator);
            app(VerificationRewards::class)->identityApproved($this);
        });
    }
}
