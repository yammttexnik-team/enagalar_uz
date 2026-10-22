<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\NannyProfile;
use App\Models\Region;
use App\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class NannySearchTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function ids(string $query = ''): array
    {
        return collect($this->getJson('/api/v1/nannies'.$query)->assertOk()->json('data'))->pluck('id')->all();
    }

    public function test_only_active_profiles_are_listed(): void
    {
        $active = $this->nannyUser(['status' => 'active'])->nannyProfile;
        $this->nannyUser(['status' => 'pending']);
        $this->nannyUser(['status' => 'draft']);

        $this->assertSame([$active->id], $this->ids());
    }

    public function test_blocked_users_are_hidden(): void
    {
        $visible = $this->nannyUser(['status' => 'active'])->nannyProfile;
        $this->nannyUser(['status' => 'active'])->update(['status' => 'blocked']);

        $this->assertSame([$visible->id], $this->ids());
    }

    public function test_filter_by_region_and_work_format(): void
    {
        $tashkent = Region::where('slug', 'tashkent-city')->first();
        $other = Region::where('id', '!=', $tashkent->id)->first();

        $a = $this->nannyUser(['status' => 'active', 'region_id' => $tashkent->id, 'work_formats' => ['hourly']])->nannyProfile;
        $b = $this->nannyUser(['status' => 'active', 'region_id' => $tashkent->id, 'work_formats' => ['full_time']])->nannyProfile;
        $c = $this->nannyUser(['status' => 'active', 'region_id' => $other->id, 'work_formats' => ['hourly']])->nannyProfile;

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $this->ids('?region=tashkent-city'));
        $this->assertSame([$a->id], $this->ids('?region=tashkent-city&work_format=hourly'));
        $this->assertEqualsCanonicalizing([$a->id, $c->id], $this->ids('?work_format=hourly'));
    }

    public function test_filter_by_price_language_experience_and_flags(): void
    {
        $cheap = $this->nannyUser(['status' => 'active', 'price_amount' => 2_000_000, 'languages' => ['uz'], 'experience_years' => 1])->nannyProfile;
        $pricey = $this->nannyUser(['status' => 'active', 'price_amount' => 6_000_000, 'languages' => ['uz', 'en'], 'experience_years' => 8, 'live_in' => true, 'is_available' => false])->nannyProfile;

        $this->assertSame([$cheap->id], $this->ids('?price_max=3000000'));
        $this->assertSame([$pricey->id], $this->ids('?language=en'));
        $this->assertSame([$pricey->id], $this->ids('?experience_min=5'));
        $this->assertSame([$pricey->id], $this->ids('?live_in=1'));
        $this->assertSame([$cheap->id], $this->ids('?available_only=1'));
        // Boshqa birlikdagi narx price_max bilan solishtirilmaydi
        $this->assertSame([], $this->ids('?price_max=3000000&price_unit=hour'));
    }

    public function test_filter_by_skill_and_sort(): void
    {
        $a = $this->nannyUser(['status' => 'active', 'rating_avg' => 3.5, 'experience_years' => 2, 'price_amount' => 5_000_000])->nannyProfile;
        $b = $this->nannyUser(['status' => 'active', 'rating_avg' => 4.9, 'experience_years' => 9, 'price_amount' => 3_000_000])->nannyProfile;
        $a->skills()->attach(Skill::where('slug', 'cooking')->value('id'));

        $this->assertSame([$a->id], $this->ids('?skill=cooking'));
        $this->assertSame([$b->id, $a->id], $this->ids('?sort=rating'));
        $this->assertSame([$b->id, $a->id], $this->ids('?sort=price_asc'));
        $this->assertSame([$b->id, $a->id], $this->ids('?sort=experience'));
    }

    public function test_verified_only_uses_active_badges(): void
    {
        $verified = $this->nannyUser(['status' => 'active'])->nannyProfile;
        $expired = $this->nannyUser(['status' => 'active'])->nannyProfile;
        $plain = $this->nannyUser(['status' => 'active'])->nannyProfile;

        $badge = Badge::where('slug', Badge::VERIFIED)->first();
        $badge->grantTo($verified->user);
        $badge->grantTo($expired->user);
        $expired->user->badges()->updateExistingPivot($badge->id, ['expires_at' => now()->subDay()]);

        $this->assertSame([$verified->id], $this->ids('?verified_only=1'));
        $this->assertSame([$verified->id], $this->ids('?badge=verified'));
        $this->assertCount(3, $this->ids());
    }

    public function test_invalid_filter_is_rejected(): void
    {
        $this->getJson('/api/v1/nannies?work_format=nonsense')->assertUnprocessable();
        $this->getJson('/api/v1/nannies?sort=random')->assertUnprocessable();
    }

    public function test_list_has_no_n_plus_one(): void
    {
        foreach (range(1, 6) as $i) {
            $this->nannyUser(['status' => 'active']);
        }

        \DB::enableQueryLog();
        $this->getJson('/api/v1/nannies')->assertOk();
        $this->assertLessThan(12, count(\DB::getQueryLog()));
    }

    public function test_public_payload_hides_contacts_and_shows_short_name(): void
    {
        $user = $this->nannyUser(['status' => 'active']);
        $profile = $user->nannyProfile;

        $list = $this->getJson('/api/v1/nannies')->assertOk()->assertJsonPath('data.0.name', 'Dilnoza K.');
        $show = $this->getJson('/api/v1/nannies/'.$profile->id)->assertOk()->assertJsonPath('data.name', 'Dilnoza K.');

        foreach ([$list->getContent(), $show->getContent()] as $body) {
            $this->assertStringNotContainsString($user->phone, $body);
            $this->assertStringNotContainsString('dilnoza_secret_', $body);
            $this->assertStringNotContainsString('Karimova', $body);
            $this->assertStringNotContainsString('passport', $body);
            $this->assertStringNotContainsString('phone', $body);
            $this->assertStringNotContainsString('telegram', $body);
        }
    }

    public function test_show_increments_views_and_hides_inactive(): void
    {
        $active = $this->nannyUser(['status' => 'active'])->nannyProfile;
        $pending = $this->nannyUser(['status' => 'pending'])->nannyProfile;

        $this->getJson('/api/v1/nannies/'.$active->id)->assertOk();
        $this->assertSame(1, NannyProfile::find($active->id)->views_count);
        $this->getJson('/api/v1/nannies/'.$pending->id)->assertNotFound();
    }

    public function test_skills_are_listed(): void
    {
        $this->getJson('/api/v1/skills')->assertOk()->assertJsonFragment(['slug' => 'cooking'])->assertJsonStructure(['data' => [['name' => ['uz', 'ru']]]]);
    }
}
