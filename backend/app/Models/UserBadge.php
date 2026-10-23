<?php

namespace App\Models;

use App\Jobs\SendBadgeConsentNotification;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserBadge extends Pivot
{
    protected $table = 'user_badges';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_public' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Admin bergan nishon (javob kutilmoqda): foydalanuvchidan "profilda ko'rsatilsinmi?" deb so'raymiz.
        static::created(function (UserBadge $pivot) {
            if ($pivot->is_public === null) {
                SendBadgeConsentNotification::dispatch((int) $pivot->user_id, (int) $pivot->badge_id)->afterCommit();
            }
        });
    }
}
