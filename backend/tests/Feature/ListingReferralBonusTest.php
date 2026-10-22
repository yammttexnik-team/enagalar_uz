<?php

namespace Tests\Feature;

use App\Enums\BonusType;
use App\Enums\NannyProfileStatus;
use App\Models\BonusTransaction;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class ListingReferralBonusTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function activate(User $nanny): void
    {
        $nanny->nannyProfile->update(['status' => NannyProfileStatus::Active, 'published_at' => now()]);
    }

    public function test_nanny_gets_default_listing_bonus_once(): void
    {
        $nanny = $this->nannyUser();

        $this->activate($nanny);
        $this->assertSame(30000, $nanny->bonusBalance());

        // Yashirib, qayta faollashtirsa — yana bonus yo'q.
        $nanny->nannyProfile->update(['status' => NannyProfileStatus::Hidden]);
        $this->activate($nanny);

        $this->assertSame(30000, $nanny->bonusBalance());
        $this->assertSame(1, BonusTransaction::where('type', 'listing')->count());
        $this->assertDatabaseHas('bonus_transactions', ['user_id' => $nanny->id, 'reference' => 'listing:nanny:'.$nanny->nannyProfile->id]);
    }

    public function test_pending_profile_gets_nothing(): void
    {
        $nanny = $this->nannyUser();
        $nanny->nannyProfile->update(['status' => NannyProfileStatus::Pending]);

        $this->assertSame(0, $nanny->bonusBalance());
    }

    public function test_listing_bonus_uses_active_campaign_amount(): void
    {
        $campaign = Campaign::create(['code' => 'BIG', 'name' => 'Big', 'listing_bonus' => 45000]);
        $nanny = $this->nannyUser();
        $nanny->update(['campaign_id' => $campaign->id]);

        $this->activate($nanny);

        $this->assertSame(45000, $nanny->bonusBalance());
        $this->assertSame($campaign->id, BonusTransaction::first()->campaign_id);
    }

    public function test_ended_campaign_falls_back_to_default(): void
    {
        $campaign = Campaign::create(['code' => 'OLD', 'name' => 'Old', 'listing_bonus' => 45000, 'ends_at' => now()->subDay()]);
        $nanny = $this->nannyUser();
        $nanny->update(['campaign_id' => $campaign->id]);

        $this->activate($nanny);

        $this->assertSame(30000, $nanny->bonusBalance());
    }

    public function test_parent_gets_bonus_only_for_first_published_vacancy(): void
    {
        $parent = $this->parentUser();
        $first = Vacancy::factory()->create(['parent_profile_id' => $parent->parentProfile->id]);
        $second = Vacancy::factory()->create(['parent_profile_id' => $parent->parentProfile->id]);

        $this->assertSame(0, $parent->bonusBalance());

        $first->publish();
        $second->publish();

        $this->assertSame(30000, $parent->bonusBalance());
        $this->assertDatabaseHas('bonus_transactions', ['reference' => 'listing:vacancy:'.$parent->parentProfile->id]);
    }

    public function test_quick_vacancy_delete_revokes_listing_bonus(): void
    {
        $parent = $this->parentUser();
        $vacancy = Vacancy::factory()->create(['parent_profile_id' => $parent->parentProfile->id]);
        $vacancy->publish();
        $this->assertSame(30000, $parent->bonusBalance());

        $vacancy->delete();

        $this->assertSame(0, $parent->bonusBalance());
    }

    public function test_late_vacancy_delete_keeps_bonus(): void
    {
        $parent = $this->parentUser();
        $vacancy = Vacancy::factory()->create(['parent_profile_id' => $parent->parentProfile->id]);
        $vacancy->publish();

        $this->travel(15)->days();
        $vacancy->delete();

        $this->assertSame(30000, $parent->bonusBalance());
    }

    public function test_referral_pays_both_sides_once(): void
    {
        $referrer = User::factory()->create();
        $nanny = $this->nannyUser();
        $nanny->update(['referred_by_id' => $referrer->id]);

        $this->activate($nanny);

        $this->assertSame(15000, $referrer->bonusBalance());
        $this->assertSame(30000 + 15000, $nanny->bonusBalance());
        $this->assertSame(2, BonusTransaction::where('type', BonusType::Referral)->where('reference', 'referral:'.$nanny->id)->count());

        // Ota-ona roli ham bo'lsa va e'lon joylasa — referal qayta berilmaydi.
        $parent = $nanny;
        $parent->parentProfile()->create();
        Vacancy::factory()->create(['parent_profile_id' => $parent->parentProfile->id])->publish();

        $this->assertSame(15000, $referrer->bonusBalance());
        $this->assertSame(30000 + 15000 + 30000, $nanny->bonusBalance());
    }

    public function test_quick_vacancy_delete_also_revokes_referral_bonuses_on_both_sides(): void
    {
        $referrer = User::factory()->create();
        $parent = $this->parentUser();
        $parent->update(['referred_by_id' => $referrer->id]);

        $vacancy = Vacancy::factory()->create(['parent_profile_id' => $parent->parentProfile->id]);
        $vacancy->publish();
        $this->assertSame(15000, $referrer->bonusBalance());
        $this->assertSame(30000 + 15000, $parent->bonusBalance());

        $vacancy->delete();

        $this->assertSame(0, $referrer->bonusBalance());
        $this->assertSame(0, $parent->bonusBalance());
        $this->assertSame(3, BonusTransaction::where('type', BonusType::Revoke)->count());

        // Qayta e'lon joylasa ham bonus qaytadan berilmaydi (reference band).
        Vacancy::factory()->create(['parent_profile_id' => $parent->parentProfile->id])->publish();
        $this->assertSame(0, $referrer->bonusBalance());
        $this->assertSame(0, $parent->bonusBalance());
    }

    public function test_referral_kept_when_user_still_has_active_nanny_profile(): void
    {
        $referrer = User::factory()->create();
        $nanny = $this->nannyUser();
        $nanny->update(['referred_by_id' => $referrer->id]);
        $this->activate($nanny);

        $nanny->parentProfile()->create();
        $vacancy = Vacancy::factory()->create(['parent_profile_id' => $nanny->parentProfile->id]);
        $vacancy->publish();
        $vacancy->delete();

        // Faqat vakansiya listing bonusi bekor; anketa va referal qoladi.
        $this->assertSame(15000, $referrer->bonusBalance());
        $this->assertSame(30000 + 15000, $nanny->bonusBalance());
    }

    public function test_revoked_referrals_do_not_use_up_referrer_max(): void
    {
        config(['bonus.referral_max_rewards' => 1]);
        $referrer = User::factory()->create();

        $parent = $this->parentUser();
        $parent->update(['referred_by_id' => $referrer->id]);
        $vacancy = Vacancy::factory()->create(['parent_profile_id' => $parent->parentProfile->id]);
        $vacancy->publish();
        $vacancy->delete();

        $nanny = $this->nannyUser();
        $nanny->update(['referred_by_id' => $referrer->id]);
        $this->activate($nanny);

        $this->assertSame(15000, $referrer->bonusBalance());
    }

    public function test_referral_amount_comes_from_referrer_campaign_and_is_capped(): void
    {
        $campaign = Campaign::create(['code' => 'REF', 'name' => 'Ref', 'referral_bonus' => 25000]);
        $referrer = User::factory()->create(['campaign_id' => $campaign->id]);

        for ($i = 0; $i < 6; $i++) {
            $nanny = $this->nannyUser(name: 'Enaga '.$i);
            $nanny->update(['referred_by_id' => $referrer->id]);
            $this->activate($nanny);
        }

        // Maksimum 5 ta mukofotlangan taklif.
        $this->assertSame(5 * 25000, $referrer->bonusBalance());
    }
}
