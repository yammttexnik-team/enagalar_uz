<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Ota-ona va enaga bog'lanishi (suhbatda ikkalasi ham yozgan). */
#[Fillable([
    'conversation_id', 'parent_id', 'nanny_id', 'connected_at', 'asked_at',
    'parent_worked', 'parent_answered_at', 'nanny_worked', 'nanny_answered_at',
])]
class Connection extends Model
{
    /** Bog'lanishdan necha kun keyin "Birga ishladingizmi?" so'raladi. */
    public const ASK_AFTER_DAYS = 3;

    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
            'asked_at' => 'datetime',
            'parent_worked' => 'boolean',
            'parent_answered_at' => 'datetime',
            'nanny_worked' => 'boolean',
            'nanny_answered_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function nanny(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nanny_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Foydalanuvchi ishtirok etgan bog'lanishlar. */
    public function scopeInvolving(Builder $query, User $user): void
    {
        $query->where(fn (Builder $q) => $q->where('parent_id', $user->id)->orWhere('nanny_id', $user->id));
    }

    /** Foydalanuvchining tomoni: parent, nanny yoki null (ishtirokchi emas). */
    public function sideOf(User $user): ?string
    {
        return match ($user->id) {
            (int) $this->parent_id => 'parent',
            (int) $this->nanny_id => 'nanny',
            default => null,
        };
    }

    public static function otherSide(string $side): string
    {
        return $side === 'parent' ? 'nanny' : 'parent';
    }

    /** Tomonning javobi: true — ishladik, false — yo'q, null — javob bermagan. */
    public function answerOf(string $side): ?bool
    {
        return $this->{$side.'_worked'};
    }

    /** Ikkalasi ham "Ha, ishladik" degan. */
    public function bothWorked(): bool
    {
        return $this->parent_worked === true && $this->nanny_worked === true;
    }

    public function userOf(string $side): ?User
    {
        return $side === 'parent' ? $this->parent : $this->nanny;
    }
}
