<?php

namespace App\Models\Concerns;

use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Moderator tekshiradigan yuklangan fayl (pasport, ma'lumotnoma).
 * Qaror qabul qilingach fayl o'chiriladi: faqat natija saqlanadi.
 */
trait ReviewedByModerator
{
    public const DISK = 'private';

    abstract protected function fileColumn(): string;

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === VerificationStatus::Pending;
    }

    public function reject(User $moderator, string $reason): void
    {
        $this->rejection_reason = $reason;
        $this->markReviewed($moderator, VerificationStatus::Rejected);
    }

    protected function markReviewed(User $moderator, VerificationStatus $status): void
    {
        $column = $this->fileColumn();

        if ($this->{$column}) {
            Storage::disk(self::DISK)->delete($this->{$column});
        }

        $this->forceFill([
            'status' => $status,
            $column => null,
            'reviewed_by' => $moderator->id,
            'reviewed_at' => now(),
        ])->save();
    }
}
