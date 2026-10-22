<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Shaxsiy ma'lumotlarga ishlov berish uchun rozilik (ZRU-547). */
#[Fillable(['user_id', 'type', 'version', 'ip', 'user_agent', 'accepted_at', 'revoked_at'])]
class Consent extends Model
{
    public const PERSONAL_DATA = 'personal_data';

    public const PASSPORT = 'passport';

    public const HEALTH = 'health';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
