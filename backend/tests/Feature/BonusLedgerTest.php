<?php

namespace Tests\Feature;

use App\Enums\BonusType;
use App\Models\BonusTransaction;
use App\Models\Campaign;
use App\Models\User;
use App\Services\Bonus\BonusService;
use App\Services\Bonus\InsufficientBonus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BonusLedgerTest extends TestCase
{
    use RefreshDatabase;

    private BonusService $bonuses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bonuses = app(BonusService::class);
    }

    public function test_award_is_idempotent_by_reference(): void
    {
        $user = User::factory()->create();

        $a = $this->bonuses->award($user, BonusType::Survey, 50000, 'survey:1');
        $b = $this->bonuses->award($user, BonusType::Survey, 50000, 'survey:1');

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, BonusTransaction::count());
        $this->assertSame(50000, $user->bonusBalance());
        $this->assertTrue($a->expires_at->isSameDay(now()->addMonths(9)));
    }

    public function test_same_reference_for_other_type_or_user_is_allowed(): void
    {
        [$u1, $u2] = User::factory()->count(2)->create();

        $this->bonuses->award($u1, BonusType::Survey, 100, 'x');
        $this->bonuses->award($u1, BonusType::Listing, 100, 'x');
        $this->bonuses->award($u2, BonusType::Survey, 100, 'x');

        $this->assertSame(3, BonusTransaction::count());
    }

    public function test_closed_campaign_gives_nothing(): void
    {
        $user = User::factory()->create();
        $off = Campaign::create(['code' => 'OFF', 'name' => 'Off', 'is_active' => false]);
        $ended = Campaign::create(['code' => 'ENDED', 'name' => 'Ended', 'ends_at' => now()->subDay()]);
        $future = Campaign::create(['code' => 'SOON', 'name' => 'Soon', 'starts_at' => now()->addDay()]);

        foreach ([$off, $ended, $future] as $i => $c) {
            $this->assertNull($this->bonuses->award($user, BonusType::Survey, 100, "s:$i", $c));
        }
        $this->assertSame(0, $user->bonusBalance());
    }

    public function test_balance_ignores_expired_before_cron_and_expire_command_writes_rows(): void
    {
        $user = User::factory()->create();
        $this->bonuses->award($user, BonusType::Survey, 50000, 'survey:1');

        $this->travel(10)->months();
        $this->assertSame(0, $user->bonusBalance());
        $this->assertSame(1, BonusTransaction::count());

        $this->artisan('bonus:expire')->assertSuccessful();
        $this->artisan('bonus:expire')->assertSuccessful();

        $this->assertSame(2, BonusTransaction::count());
        $expire = BonusTransaction::where('type', 'expire')->first();
        $this->assertSame(-50000, $expire->amount);
        $this->assertSame(0, $user->bonusBalance());
        $this->assertSame(0, (int) BonusTransaction::where('user_id', $user->id)->sum('amount'));
    }

    public function test_expiry_only_burns_unspent_remainder(): void
    {
        $user = User::factory()->create();
        $this->bonuses->award($user, BonusType::Survey, 50000, 'survey:1');
        $this->bonuses->spend($user, 20000, 'top:1', 'TOP');

        $this->assertSame(30000, $user->bonusBalance());

        $this->travel(10)->months();
        $this->assertSame(0, $user->bonusBalance());
        $this->artisan('bonus:expire');

        $this->assertSame(-30000, BonusTransaction::where('type', 'expire')->value('amount'));
        $this->assertSame(0, (int) BonusTransaction::sum('amount'));
    }

    public function test_spend_requires_balance_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $this->bonuses->award($user, BonusType::Survey, 30000, 'survey:1');

        $this->bonuses->spend($user, 25000, 'top:1', 'TOP');
        $this->bonuses->spend($user, 25000, 'top:1', 'TOP');
        $this->assertSame(5000, $user->bonusBalance());

        $this->expectException(InsufficientBonus::class);
        $this->bonuses->spend($user, 25000, 'top:2', 'TOP');
    }

    public function test_revoke_reverses_bonus_once(): void
    {
        $user = User::factory()->create();
        $tx = $this->bonuses->award($user, BonusType::Survey, 50000, 'survey:1');

        $this->bonuses->revoke($tx, 'Soxta');
        $this->bonuses->revoke($tx, 'Soxta');

        $this->assertSame(0, $user->bonusBalance());
        $this->assertSame(-50000, BonusTransaction::where('type', 'revoke')->sum('amount'));
        $this->assertSame(2, BonusTransaction::count());
    }

    public function test_revoke_is_applied_to_its_own_earning_not_earliest_expiring(): void
    {
        $user = User::factory()->create();
        $this->bonuses->award($user, BonusType::Survey, 50000, 'survey:1');
        $this->travel(1)->months();
        $listing = $this->bonuses->award($user, BonusType::Listing, 30000, 'listing:1');

        // Kechroq tugaydigan listing bekor qilinadi; so'rovnoma bonusi to'liq qolishi kerak.
        $this->bonuses->revoke($listing);
        $this->assertSame(50000, $user->bonusBalance());

        $remainders = $this->bonuses->remainders($user);
        $this->assertSame(0, $remainders[$listing->id]);

        // So'rovnoma bonusi muddati o'tdi: to'liq 50 000 kuyadi, balans 0 (avval 30 000 noto'g'ri qolardi).
        $this->travel(9)->months();
        $this->assertSame(0, $user->bonusBalance());
        $this->bonuses->expireFor($user);
        $this->assertSame(-50000, (int) BonusTransaction::where('type', 'expire')->sum('amount'));
        $this->assertSame(0, $user->bonusBalance());
    }

    public function test_revoked_earning_does_not_count_toward_lifetime_cap(): void
    {
        config(['bonus.lifetime_cap' => 80000]);
        $user = User::factory()->create();

        $this->bonuses->award($user, BonusType::Survey, 50000, 'survey:1');
        $listing = $this->bonuses->award($user, BonusType::Listing, 30000, 'listing:1');
        $this->assertNull($this->bonuses->award($user, BonusType::Referral, 15000, 'referral:1'));

        $this->bonuses->revoke($listing);

        $this->assertSame(15000, $this->bonuses->award($user, BonusType::Referral, 15000, 'referral:1')->amount);
        $this->assertSame(65000, $user->bonusBalance());
    }

    public function test_spend_and_award_run_under_user_row_lock(): void
    {
        $user = User::factory()->create();
        DB::enableQueryLog();

        $this->bonuses->award($user, BonusType::Survey, 30000, 'survey:1');
        $this->bonuses->spend($user, 10000, 'top:1', 'TOP');

        // Har ikki amal users qatorini qulflashdan boshlanadi (sqlite'da "for update" tushirib qoldiriladi,
        // shuning uchun bu yerda faqat so'rov borligini tekshiramiz).
        $locks = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "users"') && $q['bindings'] === [$user->id]);
        $this->assertGreaterThanOrEqual(2, $locks->count());
        $this->assertSame(20000, $user->bonusBalance());
    }

    public function test_manual_adjustment_and_lifetime_cap(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $this->bonuses->adjust($user, 10000, 'Sovg\'a', $admin);
        $this->bonuses->adjust($user, -4000, 'Tuzatish', $admin);
        $this->assertSame(6000, $user->bonusBalance());

        config(['bonus.lifetime_cap' => 60000]);
        $this->bonuses->award($user, BonusType::Survey, 50000, 'survey:1');
        $clipped = $this->bonuses->award($user, BonusType::Listing, 30000, 'listing:1');
        $this->assertSame(10000, $clipped->amount);
        $this->assertNull($this->bonuses->award($user, BonusType::Referral, 15000, 'referral:1'));
    }

    public function test_me_bonus_endpoint_shows_only_own_data(): void
    {
        [$me, $other] = User::factory()->count(2)->create();
        $this->bonuses->award($me, BonusType::Survey, 50000, 'survey:1', description: 'Mening bonusim');
        $this->bonuses->award($other, BonusType::Survey, 70000, 'survey:1', description: 'Begona bonus');

        $this->getJson('/api/v1/me/bonus')->assertUnauthorized();

        $response = $this->actingAs($me)->getJson('/api/v1/me/bonus')->assertOk()
            ->assertJsonPath('balance', 50000)
            ->assertJsonCount(1, 'transactions.data')
            ->assertJsonPath('referral_code', $me->fresh()->referral_code);

        $this->assertStringContainsString('/kirish?r='.$me->fresh()->referral_code, $response->json('referral_link'));
        $this->assertStringNotContainsString('Begona', $response->getContent());
        $this->assertStringNotContainsString($other->referralCode(), $response->getContent());
    }
}
