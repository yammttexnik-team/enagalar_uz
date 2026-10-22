<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'slug', 'name_uz', 'name_ru', 'description_uz', 'description_ru', 'icon', 'color',
    'grant_type', 'auto_rule', 'validity_days', 'is_active', 'sort',
])]
class Badge extends Model
{
    public const PHONE_VERIFIED = 'phone-verified';

    public const VERIFIED = 'verified';

    public const MEDICAL = 'medical-certificate';

    public const CERTIFICATE = 'certificate';

    /** Narkologik va psixonevrologik dispanser hisobida turmasligi haqida ma'lumotnoma. */
    public const DISPENSARY_CLEAR = 'dispensary-clear';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->using(UserBadge::class)
            ->withPivot(['granted_by', 'granted_at', 'expires_at'])
            ->withTimestamps();
    }

    /** Foydalanuvchiga nishon berish (bor bo'lsa, muddati yangilanadi). */
    public function grantTo(User $user, ?User $grantedBy = null): void
    {
        $user->badges()->syncWithoutDetaching([
            $this->id => [
                'granted_by' => $grantedBy?->id,
                'granted_at' => now(),
                'expires_at' => $this->validity_days ? now()->addDays($this->validity_days) : null,
            ],
        ]);
    }
}
