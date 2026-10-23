<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['connection_id', 'author_id', 'target_user_id', 'target_role', 'rating', 'text', 'status'])]
class Review extends Model
{
    /** Ko'rinadi (yozilgan zahoti). */
    public const PUBLISHED = 'published';

    /** Moderator yashirgan. */
    public const HIDDEN = 'hidden';

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Sharh qo'shilsa, yashirilsa yoki o'chirilsa — profil reytingi qayta hisoblanadi.
        static::saved(fn (Review $review) => $review->recalculateTarget());
        static::deleted(fn (Review $review) => $review->recalculateTarget());
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::PUBLISHED);
    }

    /** Baholangan profilning (enaga yoki ota-ona) rating_avg va reviews_count maydonlarini yangilaydi. */
    public function recalculateTarget(): void
    {
        $stats = self::query()->published()
            ->where('target_user_id', $this->target_user_id)
            ->where('target_role', $this->target_role)
            ->selectRaw('count(*) as cnt, avg(rating) as avg_rating')
            ->first();

        $model = $this->target_role === 'parent' ? ParentProfile::class : NannyProfile::class;

        // Query orqali: observer'lar (bonus va h.k.) ishga tushmaydi.
        $model::query()->where('user_id', $this->target_user_id)->update([
            'rating_avg' => round((float) ($stats->avg_rating ?? 0), 2),
            'reviews_count' => (int) ($stats->cnt ?? 0),
        ]);
    }
}
