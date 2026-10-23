<?php

namespace Tests\Feature;

use App\Enums\BonusType;
use App\Enums\NannyProfileStatus;
use App\Enums\VacancyStatus;
use App\Models\BonusTransaction;
use App\Models\NannyProfile;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** Faol e'lon; birinchi e'lon bonusini (listing) olib tashlab, balansni aniq beramiz. */
    private function parentWithVacancy(int $balance): array
    {
        $parent = $this->parentUser();
        $vacancy = Vacancy::factory()->active()->create(['parent_profile_id' => $parent->parentProfile->id]);
        $this->setBalance($parent, $balance);

        return [$parent, $vacancy];
    }

    private function setBalance(User $user, int $balance): void
    {
        BonusTransaction::where('user_id', $user->id)->delete();
        if ($balance > 0) {
            BonusTransaction::create([
                'user_id' => $user->id, 'amount' => $balance, 'type' => BonusType::Manual,
                'reference' => 'test', 'expires_at' => now()->addMonths(9),
            ]);
        }
    }

    private function buy(string $target, int $id, string $service)
    {
        return $this->postJson('/api/v1/my/promotions', compact('target', 'id', 'service'));
    }

    public function test_prices_endpoint(): void
    {
        $this->getJson('/api/v1/promotions/prices')->assertOk()
            ->assertJsonPath('data.top.price', 25000)->assertJsonPath('data.top.days', 7)
            ->assertJsonPath('data.urgent.price', 10000)->assertJsonPath('data.urgent.days', 3);
    }

    public function test_parent_buys_top_with_enough_balance(): void
    {
        [$parent, $vacancy] = $this->parentWithVacancy(30000);
        Sanctum::actingAs($parent);

        $this->buy('vacancy', $vacancy->id, 'top')->assertOk()->assertJsonPath('data.balance', 5000);

        $vacancy->refresh();
        $this->assertTrue($vacancy->top_until->isSameDay(now()->addDays(7)));
        $this->assertSame(5000, $parent->bonusBalance());
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $parent->id, 'type' => 'spend', 'amount' => -25000,
            'reference' => 'spend:top:vacancy:'.$vacancy->id.':'.now()->toDateString(),
        ]);
    }

    public function test_insufficient_balance_is_rejected(): void
    {
        [$parent, $vacancy] = $this->parentWithVacancy(20000);
        Sanctum::actingAs($parent);

        $this->buy('vacancy', $vacancy->id, 'top')->assertStatus(422)->assertJsonValidationErrors('service');

        $this->assertNull($vacancy->fresh()->top_until);
        $this->assertSame(20000, $parent->bonusBalance());
    }

    public function test_buying_again_extends_from_current_end(): void
    {
        [$parent, $vacancy] = $this->parentWithVacancy(60000);
        Sanctum::actingAs($parent);

        $this->buy('vacancy', $vacancy->id, 'top')->assertOk();
        $firstEnd = $vacancy->fresh()->top_until;

        $this->travel(2)->days();
        $this->buy('vacancy', $vacancy->id, 'top')->assertOk();

        $this->assertTrue($vacancy->fresh()->top_until->equalTo($firstEnd->copy()->addDays(7)));
        $this->assertSame(10000, $parent->bonusBalance());
    }

    public function test_same_day_repeat_is_idempotent(): void
    {
        [$parent, $vacancy] = $this->parentWithVacancy(60000);
        Sanctum::actingAs($parent);

        $this->buy('vacancy', $vacancy->id, 'urgent')->assertOk();
        $end = $vacancy->fresh()->urgent_until;
        $this->buy('vacancy', $vacancy->id, 'urgent')->assertOk()->assertJsonPath('data.balance', 50000);

        $this->assertTrue($vacancy->fresh()->urgent_until->equalTo($end));
        $this->assertSame(1, BonusTransaction::where('type', 'spend')->count());
    }

    public function test_only_owner_can_buy(): void
    {
        [, $vacancy] = $this->parentWithVacancy(30000);
        $other = $this->parentUser();
        $this->setBalance($other, 50000);
        Sanctum::actingAs($other);

        $this->buy('vacancy', $vacancy->id, 'top')->assertForbidden();

        $nanny = $this->nannyUser(['status' => NannyProfileStatus::Active, 'published_at' => now()]);
        $this->buy('nanny', $nanny->nannyProfile->id, 'top')->assertForbidden();

        $this->assertSame(50000, $other->bonusBalance());
        $this->assertNull($vacancy->fresh()->top_until);
    }

    public function test_inactive_listing_is_rejected(): void
    {
        [$parent, $vacancy] = $this->parentWithVacancy(30000);
        $vacancy->update(['status' => VacancyStatus::Closed]);
        Sanctum::actingAs($parent);
        $this->buy('vacancy', $vacancy->id, 'top')->assertStatus(422)->assertJsonValidationErrors('id');

        $nanny = $this->nannyUser(['status' => NannyProfileStatus::Pending]);
        $this->setBalance($nanny, 30000);
        Sanctum::actingAs($nanny);
        $this->buy('nanny', $nanny->nannyProfile->id, 'urgent')->assertStatus(422);

        $this->assertSame(30000, $nanny->bonusBalance());
    }

    public function test_nanny_buys_urgent_and_public_resource_shows_it(): void
    {
        $nanny = $this->nannyUser(['status' => NannyProfileStatus::Active, 'published_at' => now()]);
        $this->setBalance($nanny, 10000);
        Sanctum::actingAs($nanny);

        $this->buy('nanny', $nanny->nannyProfile->id, 'urgent')->assertOk()->assertJsonPath('data.balance', 0);

        $this->getJson('/api/v1/nannies/'.$nanny->nannyProfile->id)->assertOk()
            ->assertJsonPath('data.is_urgent', true)->assertJsonPath('data.is_top', false);
    }

    public function test_top_listings_come_first_in_public_search(): void
    {
        $vacancies = Vacancy::factory()->active()->count(3)->sequence(
            ['published_at' => now()->subDays(3)],
            ['published_at' => now()->subDays(2)],
            ['published_at' => now()->subDay()],
        )->create();
        $vacancies[0]->forceFill(['top_until' => now()->addDays(2)])->save();
        $vacancies[1]->forceFill(['top_until' => now()->addDays(5)])->save();
        // Muddati o'tgan TOP — oddiy tartibda.
        $vacancies[2]->forceFill(['top_until' => now()->subDay()])->save();

        $res = $this->getJson('/api/v1/vacancies')->assertOk();
        $this->assertSame([$vacancies[1]->id, $vacancies[0]->id, $vacancies[2]->id], array_column($res->json('data'), 'id'));
        $res->assertJsonPath('data.0.is_top', true)->assertJsonPath('data.2.is_top', false);

        // Filtr bilan ham ishlaydi.
        $vacancies[2]->update(['is_urgent' => true]);
        $vacancies[0]->forceFill(['urgent_until' => now()->addDay()])->save();
        $ids = array_column($this->getJson('/api/v1/vacancies?is_urgent=1')->json('data'), 'id');
        $this->assertSame([$vacancies[0]->id, $vacancies[2]->id], $ids);

        $n1 = NannyProfile::factory()->active()->create(['published_at' => now()]);
        $n2 = NannyProfile::factory()->active()->create(['published_at' => now()->subWeek()]);
        $n2->forceFill(['top_until' => now()->addDay()])->save();

        $ids = array_column($this->getJson('/api/v1/nannies?sort=rating')->json('data'), 'id');
        $this->assertSame([$n2->id, $n1->id], $ids);
    }
}
