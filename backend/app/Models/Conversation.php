<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

#[Fillable(['vacancy_id', 'last_message_at', 'pair_key'])]
class Conversation extends Model
{
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['last_read_message_id', 'muted', 'blocked_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /** Suhbat ishtirokchisi (participants yuklangan bo'lishi kerak, aks holda yuklanadi). */
    public function participantFor(User $user): ?User
    {
        return $this->participants->firstWhere('id', $user->id);
    }

    /** Suhbatdagi ikkinchi ishtirokchi. */
    public function otherFor(User $user): ?User
    {
        return $this->participants->first(fn (User $p) => $p->id !== $user->id);
    }

    /** Ikki foydalanuvchi uchun tartiblangan juftlik kaliti (masalan "3:17"). */
    public static function pairKey(User $a, User $b): string
    {
        return min($a->id, $b->id).':'.max($a->id, $b->id);
    }

    /** Ikki foydalanuvchi o'rtasidagi suhbatni topadi yoki yaratadi (pair_key unikal, poyga bo'lsa qayta qidiradi). */
    public static function between(User $a, User $b, ?Vacancy $vacancy = null): self
    {
        $key = self::pairKey($a, $b);

        $existing = self::query()->where('pair_key', $key)->first()
            // pair_key qo'yilmagan eski suhbatlar
            ?? self::query()
                ->whereNull('pair_key')
                ->whereHas('participants', fn ($q) => $q->whereKey($a->id))
                ->whereHas('participants', fn ($q) => $q->whereKey($b->id))
                ->first();

        if ($existing) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($a, $b, $vacancy, $key) {
                $conversation = self::create(['vacancy_id' => $vacancy?->id, 'pair_key' => $key]);
                $conversation->participants()->attach([$a->id, $b->id]);

                return $conversation;
            });
        } catch (UniqueConstraintViolationException) {
            // Parallel so'rov oldin yaratib bo'lgan.
            return self::query()->where('pair_key', $key)->firstOrFail();
        }
    }
}
