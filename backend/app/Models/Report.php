<?php

namespace App\Models;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['reporter_id', 'reportable_type', 'reportable_id', 'reason', 'comment', 'status', 'handled_by'])]
class Report extends Model
{
    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Shikoyat qilingan suhbat (agar shikoyat suhbat haqida bo'lsa). */
    public function conversation(): ?Conversation
    {
        return $this->reportable instanceof Conversation ? $this->reportable : null;
    }

    /** Shikoyat qilingan foydalanuvchi: suhbatdagi shikoyatchidan boshqa ishtirokchi. */
    public function reportedUser(): ?User
    {
        return $this->conversation()?->participants()->where('users.id', '!=', $this->reporter_id)->first();
    }
}
