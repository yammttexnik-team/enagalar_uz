<?php

namespace App\Services\Bonus;

use App\Enums\BonusType;
use App\Enums\NannyProfileStatus;
use App\Enums\UserStatus;
use App\Models\BonusTransaction;
use App\Models\NannyProfile;
use App\Models\User;
use App\Models\Vacancy;

/** Birinchi faol e'lon/anketa uchun bonus va referal bonusi. */
class ListingRewards
{
    public function __construct(private BonusService $bonuses) {}

    public function nannyActivated(NannyProfile $profile): void
    {
        $user = $profile->user;
        if (! $user) {
            return;
        }

        $this->listing($user, 'listing:nanny:'.$profile->id, 'Anketa faollashtirildi');
    }

    public function vacancyPublished(Vacancy $vacancy): void
    {
        $user = $vacancy->parentProfile?->user;
        if (! $user) {
            return;
        }

        // Faqat ota-onaning birinchi e'loni (reference parent_profile_id bo'yicha).
        $this->listing($user, 'listing:vacancy:'.$vacancy->parent_profile_id, "Birinchi e'lon joylandi");
    }

    /** E'lon 14 kundan oldin o'chirilsa — listing bonusini bekor qilish (boshqa faol e'lon qolmasa). */
    public function vacancyDeleted(Vacancy $vacancy): void
    {
        $user = $vacancy->parentProfile?->user;
        if (! $user || ! $vacancy->published_at) {
            return;
        }

        $tx = BonusTransaction::where('user_id', $user->id)->where('type', BonusType::Listing)
            ->where('reference', 'listing:vacancy:'.$vacancy->parent_profile_id)->first();

        if (! $tx || $tx->created_at->lt(now()->subDays(config('bonus.listing_revoke_days')))) {
            return;
        }

        if ($vacancy->parentProfile->vacancies()->where('status', 'active')->exists()) {
            return;
        }

        $this->bonuses->revoke($tx, "E'lon tez o'chirildi");

        // Shu e'lon sababli (listing bonusidan keyin) berilgan referal bonuslari ham bekor — ikkala tomonda,
        // agar foydalanuvchida boshqa faol anketa qolmagan bo'lsa.
        if ($user->nannyProfile()->where('status', NannyProfileStatus::Active)->exists()) {
            return;
        }

        BonusTransaction::where('type', BonusType::Referral)->where('reference', 'referral:'.$user->id)->where('amount', '>', 0)
            ->where('created_at', '>=', $tx->created_at)
            ->get()->each(fn (BonusTransaction $referral) => $this->bonuses->revoke($referral, "Taklif qilingan do'st e'loni tez o'chirildi"));
    }

    private function listing(User $user, string $reference, string $description): void
    {
        $campaign = $this->bonuses->campaignFor($user);
        $amount = $campaign?->listing_bonus ?? config('bonus.listing');

        $this->bonuses->award($user, BonusType::Listing, $amount, $reference, $campaign, $description);
        $this->referral($user);
    }

    /** Taklif qilingan foydalanuvchi birinchi e'lonini faollashtirsa — ikkala tomonga. */
    private function referral(User $referred): void
    {
        $referrer = $referred->referredBy;
        if (! $referrer || $referrer->status !== UserStatus::Active || $referrer->is($referred)) {
            return;
        }

        $reference = 'referral:'.$referred->id;

        // Sanash va berish taklif qilganning qulfi ostida: parallel faollashuvlar maksimumdan oshmaydi.
        $this->bonuses->forUser($referrer, function () use ($referrer, $referred, $reference) {
            $already = BonusTransaction::where('user_id', $referrer->id)->where('type', BonusType::Referral)->where('reference', $reference)->exists();

            if (! $already) {
                $max = (int) config('bonus.referral_max_rewards');
                // Bekor qilingan referallar hisobga kirmaydi.
                $count = BonusTransaction::where('user_id', $referrer->id)->where('type', BonusType::Referral)->where('amount', '>', 0)
                    ->whereNotIn('id', $this->bonuses->revokedIds($referrer))->count();
                if ($max > 0 && $count >= $max) {
                    return;
                }
            }

            $campaign = $this->bonuses->campaignFor($referrer);
            $amount = $campaign?->referral_bonus ?? config('bonus.referral');

            $this->bonuses->award($referrer, BonusType::Referral, $amount, $reference, $campaign, "Taklif qilgan do'stingiz e'lon joyladi");
            $this->bonuses->award($referred, BonusType::Referral, $amount, $reference, $campaign, "Do'stingiz taklifi bo'yicha bonus");
        });
    }
}
