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

    /** Pullik xizmatlar uchun (hozircha ishlatilmaydi). Balans yetmasa InsufficientBonus. */
    public function spend(User $user, int $amount, string $reference, string $description): BonusTransaction
    {
        return DB::transaction(function () use ($user, $amount, $reference, $description) {
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
     * Har bir topilmaning sarflanmagan qoldig'i (FIFO: avval tez tugaydigan).
     *
     * @return Collection<int, int> topilma id => qoldiq
     */
    public function remainders(User $user, bool $expiredOnly = false): Collection
    {
        $rows = BonusTransaction::where('user_id', $user->id)->get();

        $debits = -$rows->where('amount', '<', 0)->where('type', '!=', BonusType::Expire)->sum('amount');
        $expired = $rows->where('type', BonusType::Expire)->mapWithKeys(
            fn ($r) => [(int) str_replace('expire:', '', (string) $r->reference) => -$r->amount]
        );

        $earnings = $rows->where('amount', '>', 0)
            ->sortBy(fn ($r) => [$r->expires_at?->timestamp ?? PHP_INT_MAX, $r->id])
            ->values();

        $result = collect();
        foreach ($earnings as $e) {
            $left = $e->amount - ($expired[$e->id] ?? 0);
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

        $earned = (int) BonusTransaction::where('user_id', $user->id)
            ->whereIn('type', array_map(fn ($t) => $t->value, BonusType::earned()))
            ->where('amount', '>', 0)->sum('amount');

        return min($amount, max(0, $cap - $earned));
    }

    private function insert(array $attributes): ?BonusTransaction
    {
        try {
            return BonusTransaction::create($attributes);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }
}
