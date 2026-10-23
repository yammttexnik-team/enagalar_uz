<?php

namespace App\Services\Bonus;

use App\Enums\BonusType;
use App\Models\BonusTransaction;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bonus daftari (ledger). Barcha yozuvlar so'mda, ishorali (+ topilgan, - sarflangan/bekor/kuygan).
 * Idempotentlik: (user, type, reference) bo'yicha.
 */
class BonusService
{
    /**
     * Bonus beradi. Allaqachon berilgan bo'lsa (reference) mavjud yozuvni qaytaradi.
     * Kampaniya faol bo'lmasa, miqdor 0 bo'lsa yoki umr bo'yi tavan to'lsa — null.
     */
    public function award(
        User $user,
        BonusType $type,
        int $amount,
        string $reference,
        ?Campaign $campaign = null,
        ?string $description = null,
        ?User $by = null,
    ): ?BonusTransaction {
        // Tavan tekshiruvi va yozish bitta qulf ostida: parallel so'rovlar tavandan oshirib yubormaydi.
        return $this->forUser($user, function () use ($user, $type, $amount, $reference, $campaign, $description, $by) {
            $existing = $this->find($user, $type, $reference);
            if ($existing) {
                return $existing;
            }

            if ($amount <= 0 || ($campaign && ! $campaign->isRunning())) {
                return null;
            }

            if (in_array($type, BonusType::earned(), true)) {
                $amount = $this->clipToCap($user, $amount);
                if ($amount <= 0) {
                    return null;
                }
            }

            return $this->insert([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
                'campaign_id' => $campaign?->id,
                'reference' => $reference,
                'expires_at' => now()->addMonths(config('bonus.expiry_months')),
                'created_by' => $by?->id,
            ]) ?? $this->find($user, $type, $reference);
        });
    }

    /**
     * Foydalanuvchi qatorini (users) FOR UPDATE bilan qulflab, callback'ni tranzaksiyada bajaradi.
     * Hisoblash-keyin-yozish (tavan, limit, balans) amallari shu orqali ketma-ket bajariladi.
     * sqlite'da lockForUpdate e'tiborga olinmaydi (u yerda yozuv baribir ketma-ket).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function forUser(User $user, callable $callback): mixed
    {
        return DB::transaction(function () use ($user, $callback) {
            DB::table('users')->where('id', $user->id)->lockForUpdate()->value('id');

            return $callback();
        });
    }

    /**
     * `revoke:<id>` yozuvi bilan bekor qilingan topilmalar id lari.
     *
     * @return Collection<int, int>
     */
    public function revokedIds(User $user): Collection
    {
        return BonusTransaction::where('user_id', $user->id)->where('type', BonusType::Revoke)->pluck('reference')
            ->map(fn ($ref) => $this->referencedId('revoke:', $ref))->filter()->values();
    }

    /** Qo'lda tuzatish (admin): musbat — beradi (muddatli), manfiy — ayiradi. */
    public function adjust(User $user, int $amount, string $reason, User $by): ?BonusTransaction
    {
        if ($amount === 0) {
            return null;
        }

        return $this->insert([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => BonusType::Manual,
            'description' => $reason,
            'expires_at' => $amount > 0 ? now()->addMonths(config('bonus.expiry_months')) : null,
            'created_by' => $by->id,
        ]);
    }

    /** Berilgan bonusni bekor qiladi (soxta so'rovnoma, tez o'chirilgan e'lon). Idempotent. */
    public function revoke(BonusTransaction $original, ?string $reason = null, ?User $by = null): ?BonusTransaction
    {
        if ($original->amount <= 0 || $original->type === BonusType::Revoke) {
            return null;
        }

        $reference = 'revoke:'.$original->id;
        $user = $original->user;

        return $this->find($user, BonusType::Revoke, $reference) ?? $this->insert([
            'user_id' => $original->user_id,
            'amount' => -$original->amount,
            'type' => BonusType::Revoke,
            'description' => $reason ?? 'Bonus bekor qilindi',
            'campaign_id' => $original->campaign_id,
            'reference' => $reference,
            'created_by' => $by?->id,
        ]);
    }

    /** Pullik xizmatlar uchun (PromotionService). Balans yetmasa InsufficientBonus. */
    public function spend(User $user, int $amount, string $reference, string $description): BonusTransaction
    {
        return $this->forUser($user, function () use ($user, $amount, $reference, $description) {
            if ($existing = $this->find($user, BonusType::Spend, $reference)) {
                return $existing;
            }

            if ($amount <= 0 || $this->balance($user) < $amount) {
                throw new InsufficientBonus('Bonus yetarli emas.');
            }

            return $this->insert([
                'user_id' => $user->id,
                'amount' => -$amount,
                'type' => BonusType::Spend,
                'description' => $description,
                'reference' => $reference,
            ]);
        });
    }

    /** Joriy balans: daftar yig'indisi minus muddati o'tgan, lekin hali kuydirilmagan qoldiq. */
    public function balance(User $user): int
    {
        $total = (int) BonusTransaction::where('user_id', $user->id)->sum('amount');
        $pending = $this->remainders($user, expiredOnly: true)->sum();

        return max(0, $total - $pending);
    }

    /** Muddati o'tgan topilmalar qoldig'ini `expire` yozuvi bilan kuydiradi. Yozilgan yozuvlar soni. */
    public function expireFor(User $user): int
    {
        $count = 0;
        foreach ($this->remainders($user, expiredOnly: true) as $earningId => $remaining) {
            if ($remaining <= 0) {
                continue;
            }
            $created = $this->insert([
                'user_id' => $user->id,
                'amount' => -$remaining,
                'type' => BonusType::Expire,
                'description' => "Bonus muddati o'tdi",
                'reference' => 'expire:'.$earningId,
            ]);
            $count += $created ? 1 : 0;
        }

        return $count;
    }

    /** Muddati o'tgan topilmasi bor foydalanuvchilar uchun expireFor. */
    public function expireAll(): int
    {
        $count = 0;
        BonusTransaction::where('amount', '>', 0)->whereNotNull('expires_at')->where('expires_at', '<=', now())
            ->distinct()->pluck('user_id')
            ->each(function ($id) use (&$count) {
                if ($user = User::withTrashed()->find($id)) {
                    $count += $this->expireFor($user);
                }
            });

        return $count;
    }

    /**
     * Har bir topilmaning sarflanmagan qoldig'i. `revoke:<id>` va `expire:<id>` yozuvlari aynan o'sha
     * topilmadan ayiriladi; qolgan chiqimlar (sarf, qo'lda ayirish) FIFO: avval tez tugaydigan.
     *
     * @return Collection<int, int> topilma id => qoldiq
     */
    public function remainders(User $user, bool $expiredOnly = false): Collection
    {
        $rows = BonusTransaction::where('user_id', $user->id)->get();
        $ids = $rows->pluck('id')->flip();

        // Topilmaga bog'langan chiqimlar (id => summa) va bog'lanmagan chiqimlar yig'indisi.
        $expired = [];
        $revoked = [];
        $debits = 0;
        foreach ($rows->where('amount', '<', 0) as $r) {
            $id = match ($r->type) {
                BonusType::Expire => $this->referencedId('expire:', $r->reference),
                BonusType::Revoke => $this->referencedId('revoke:', $r->reference),
                default => null,
            };

            if ($id && $r->type === BonusType::Expire) {
                $expired[$id] = ($expired[$id] ?? 0) - $r->amount;
            } elseif ($id && isset($ids[$id])) {
                $revoked[$id] = ($revoked[$id] ?? 0) - $r->amount;
            } else {
                $debits -= $r->amount;
            }
        }

        $earnings = $rows->where('amount', '>', 0)
            ->sortBy(fn ($r) => [$r->expires_at?->timestamp ?? PHP_INT_MAX, $r->id])
            ->values();

        $result = collect();
        foreach ($earnings as $e) {
            $left = max(0, $e->amount - ($revoked[$e->id] ?? 0) - ($expired[$e->id] ?? 0));
            $take = min($left, max(0, $debits));
            $debits -= $take;
            $left -= $take;

            $isExpired = $e->expires_at && $e->expires_at->lte(now());
            if ($expiredOnly && ! ($isExpired && ! isset($expired[$e->id]))) {
                continue;
            }
            $result[$e->id] = max(0, $left);
        }

        return $result;
    }

    /** Kampaniya kodi bo'yicha foydalanuvchining hozir faol kampaniyasi. */
    public function campaignFor(User $user): ?Campaign
    {
        $campaign = $user->campaign;

        return $campaign && $campaign->isRunning() ? $campaign : null;
    }

    private function find(User $user, BonusType $type, string $reference): ?BonusTransaction
    {
        return BonusTransaction::where('user_id', $user->id)->where('type', $type)->where('reference', $reference)->first();
    }

    private function clipToCap(User $user, int $amount): int
    {
        $cap = (int) config('bonus.lifetime_cap');
        if ($cap <= 0) {
            return $amount;
        }

        // Bekor qilingan topilmalar tavanga kirmaydi.
        $earned = (int) BonusTransaction::where('user_id', $user->id)
            ->whereIn('type', array_map(fn ($t) => $t->value, BonusType::earned()))
            ->where('amount', '>', 0)
            ->whereNotIn('id', $this->revokedIds($user))
            ->sum('amount');

        return min($amount, max(0, $cap - $earned));
    }

    private function referencedId(string $prefix, ?string $reference): ?int
    {
        if (! $reference || ! str_starts_with($reference, $prefix)) {
            return null;
        }
        $id = substr($reference, strlen($prefix));

        return ctype_digit($id) ? (int) $id : null;
    }

    private function insert(array $attributes): ?BonusTransaction
    {
        try {
            // Savepoint: PostgreSQL'da unique xatosi tashqi tranzaksiyani buzmasligi uchun.
            return DB::transaction(fn () => BonusTransaction::create($attributes));
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }
}
