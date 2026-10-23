<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Pullik xizmatlar (TOP, "Shoshilinch") muddati: `top_until`, `urgent_until`.
 * Ustunlar fillable emas — faqat PromotionService orqali yoziladi.
 */
trait Promotable
{
    public function initializePromotable(): void
    {
        $this->mergeCasts(['top_until' => 'datetime', 'urgent_until' => 'datetime']);
    }

    public function isTop(): bool
    {
        return $this->top_until !== null && $this->top_until->isFuture();
    }

    public function hasPaidUrgent(): bool
    {
        return $this->urgent_until !== null && $this->urgent_until->isFuture();
    }

    /** Faol TOP e'lonlar birinchi (keyinroq tugaydigani yuqorida), qolgani — keyingi tartib bo'yicha. */
    public function scopeTopFirst(Builder $query): void
    {
        $now = now();
        $column = $query->qualifyColumn('top_until');

        $query->orderByRaw("case when {$column} > ? then 1 else 0 end desc", [$now])
            ->orderByRaw("case when {$column} > ? then {$column} end desc", [$now]);
    }
}
