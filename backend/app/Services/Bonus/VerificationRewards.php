<?php

namespace App\Services\Bonus;

use App\Enums\BonusType;
use App\Models\BonusTransaction;
use App\Models\Document;
use App\Models\IdentityVerification;
use App\Models\User;

/**
 * Moderator tasdiqlaganda: pasport uchun bonus, har bir hujjat uchun qo'shimcha bonus.
 * Ikkalasi jami `bonus.verification_max` dan oshmaydi. Idempotent: (user, tur, reference).
 */
class VerificationRewards
{
    public function __construct(private BonusService $bonuses) {}

    public function identityApproved(IdentityVerification $verification): void
    {
        if ($user = $verification->user) {
            $this->reward($user, BonusType::Verification, (int) config('bonus.verification'), 'verification:'.$verification->id, 'Pasport tasdiqlandi');
        }
    }

    public function documentApproved(Document $document): void
    {
        if ($user = $document->user) {
            $this->reward($user, BonusType::Document, (int) config('bonus.document'), 'document:'.$document->id, 'Hujjat tasdiqlandi');
        }
    }

    private function reward(User $user, BonusType $type, int $amount, string $reference, string $description): void
    {
        // Sanash va berish foydalanuvchi qulfi ostida: parallel tasdiqlar maksimumdan oshmaydi.
        $this->bonuses->forUser($user, function () use ($user, $type, $amount, $reference, $description) {
            $max = (int) config('bonus.verification_max');
            if ($max > 0) {
                $given = (int) BonusTransaction::where('user_id', $user->id)
                    ->whereIn('type', [BonusType::Verification->value, BonusType::Document->value])
                    ->where('amount', '>', 0)
                    ->where('reference', '!=', $reference)
                    ->whereNotIn('id', $this->bonuses->revokedIds($user))
                    ->sum('amount');
                $amount = min($amount, max(0, $max - $given));
            }

            $this->bonuses->award($user, $type, $amount, $reference, description: $description);
        });
    }
}
