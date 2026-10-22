<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Pasport orqali shaxsni tasdiqlash so'rovi.
 * Shaxsiy maydonlar bazada shifrlangan holda saqlanadi, pasport rasmi tekshiruvdan keyin o'chiriladi.
 */
#[Fillable(['user_id', 'full_name', 'passport_series', 'passport_number', 'pinfl', 'birth_date', 'document_path', 'status'])]
#[Hidden(['passport_series', 'passport_number', 'pinfl', 'birth_date', 'document_path'])]
class IdentityVerification extends Model
{
    public const DISK = 'private';

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approve(User $moderator): void
    {
        DB::transaction(function () use ($moderator) {
            $this->markReviewed($moderator, VerificationStatus::Approved);
            Badge::where('slug', Badge::VERIFIED)->firstOrFail()->grantTo($this->user, $moderator);
        });
    }

    public function reject(User $moderator, string $reason): void
    {
        $this->rejection_reason = $reason;
        $this->markReviewed($moderator, VerificationStatus::Rejected);
    }

    private function markReviewed(User $moderator, VerificationStatus $status): void
    {
        if ($this->document_path) {
            Storage::disk(self::DISK)->delete($this->document_path);
        }

        $this->forceFill([
            'status' => $status,
            'document_path' => null,
            'reviewed_by' => $moderator->id,
            'reviewed_at' => now(),
        ])->save();
    }
}
