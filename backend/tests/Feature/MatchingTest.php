<?php

namespace Tests\Feature;

use App\Enums\NannyProfileStatus;
use App\Enums\UserStatus;
use App\Enums\VacancyStatus;
use App\Jobs\ComputeMatches;
use App\Models\AiRequest;
use App\Models\District;
use App\Models\MatchResult;
use App\Models\NannyProfile;
use App\Models\Region;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Matching\ClaudeMatchRanker;
use App\Services\Matching\MatchingService;
use App\Services\Matching\MatchRanker;
use App\Services\Matching\RuleBasedMatchRanker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesListingActors;
use Tests\Support\FakeMatchRanker;
use Tests\TestCase;

class MatchingTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function fake(?\Closure $respond = null): FakeMatchRanker
    {
        $fake = new FakeMatchRanker($respond);
        $this->app->bind(MatchRanker::class, fn () => $fake);

        return $fake;
    }

    private function activeNanny(array $profile = [], string $name = 'Dilnoza Karimova'): NannyProfile
    {
        return $this->nannyUser($profile + ['price_amount' => 3_500_000, 'status' => NannyProfileStatus::Active, 'published_at' => now()], $name)->nannyProfile;
    }

    private function activeVacancy(?User $parent = null, array $attrs = []): Vacancy
    {
        $parent ??= $this->parentUser();

        return Vacancy::factory()->active()->create($attrs + ['parent_profile_id' => $parent->parentProfile->id]);
    }

    public function test_prefilter_keeps_only_matching_active_nannies(): void
    {
        $fake = $this->fake();
        $otherRegion = Region::query()->orderByDesc('id')->value('id');

        $ok = $this->activeNanny();
        $this->activeNanny(['status' => NannyProfileStatus::Pending]);
        $this->activeNanny(['region_id' => $otherRegion]);
        $this->activeNanny(['is_available' => false]);
        $this->activeNanny(['price_amount' => 6_000_000]); // byudjetdan +20% dan baland
        $this->activeNanny(['languages' => ['ru']]);
        $tolerated = $this->activeNanny(['price_amount' => 4_700_000]); // 4M * 1.2 = 4.8M ichida
        $blocked = $this->activeNanny();
        $blocked->user->update(['status' => UserStatus::Blocked]);

        $vacancy = $this->activeVacancy();
        app(MatchingService::class)->forVacancy($vacancy);

        $ids = collect($fake->calls[0]['candidates'])->pluck('id')->sort()->values()->all();
        $this->assertSame(collect([$ok->id, $tolerated->id])->sort()->values()->all(), $ids);
    }

    public function test_payload_is_anonymized(): void
    {
        $fake = $this->fake();
        $nanny = $this->activeNanny([
            'bio' => 'Menga +998 90 123-45-67 ga qo\'ng\'iroq qiling, telegram @dilnoza_k yoki https://t.me/dilnoza. Email: dil@mail.uz. 5 yillik tajriba, bolalarni yaxshi ko\'raman.',
        ]);
        $vacancy = $this->activeVacancy(null, ['description' => 'Aloqa: 901234567, @aziza_ota. Kunduzi bolaga qarash kerak.']);

        app(MatchingService::class)->forVacancy($vacancy);

        $json = json_encode($fake->calls[0], JSON_UNESCAPED_UNICODE);
        foreach (['Dilnoza', 'Karimova', 'Aziza', 'Rahimova', $nanny->user->telegram_username, '998', '123-45-67', '901234567', '@', 't.me', 'mail.uz', 'phone', 'passport'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $json, "Payloadda '$forbidden' bor");
        }
        $this->assertStringContainsString('5 yillik tajriba', $json);
        $this->assertLessThanOrEqual(400, mb_strlen($fake->calls[0]['candidates'][0]['bio']));
    }

    public function test_bio_is_truncated_to_400_chars(): void
    {
        $fake = $this->fake();
        $this->activeNanny(['bio' => str_repeat('yaxshi enaga ', 100)]);

        app(MatchingService::class)->forVacancy($this->activeVacancy());

        $this->assertSame(400, mb_strlen($fake->calls[0]['candidates'][0]['bio']));
    }

    public function test_unknown_ids_from_ranker_are_dropped(): void
    {
        $nanny = $this->activeNanny();
        $this->fake(fn ($s, $c) => [
            ['id' => 999999, 'score' => 99, 'reason_uz' => 'x', 'reason_ru' => 'x'],
            ['id' => $nanny->id, 'score' => 70, 'reason_uz' => 'Yaxshi', 'reason_ru' => 'Хорошо'],
        ]);

        $matches = app(MatchingService::class)->forVacancy($this->activeVacancy());

        $this->assertCount(1, $matches);
        $this->assertSame($nanny->id, $matches[0]->nanny_profile_id);
        $this->assertSame('ai', $matches[0]->source);
        $this->assertSame(1, AiRequest::where('feature', 'matching')->count());
    }

    public function test_falls_back_to_rules_when_claude_throws(): void
    {
        $this->activeNanny();
        $claude = new class extends ClaudeMatchRanker
        {
            protected function callApi(string $system, string $userContent, string $model): array
            {
                throw new \RuntimeException('network down');
            }
        };
        $this->app->bind(MatchRanker::class, fn () => $claude);

        $vacancy = $this->activeVacancy();
        $matches = app(MatchingService::class)->forVacancy($vacancy);

        $this->assertCount(1, $matches);
        $this->assertSame('rules', $matches[0]->source);
        $this->assertNotSame('', $matches[0]->reason_uz);
        $this->assertSame('error', AiRequest::where('feature', 'matching')->value('status'));
    }

    public function test_daily_limit_switches_to_rules(): void
    {
        $this->activeNanny();
        $fake = $this->fake();
        $parent = $this->parentUser();
        $vacancy = $this->activeVacancy($parent);

        for ($i = 0; $i < config('services.anthropic.daily_limit'); $i++) {
            AiRequest::create(['user_id' => $parent->id, 'feature' => 'matching', 'model' => 'm', 'status' => 'ok']);
        }

        $matches = app(MatchingService::class)->forVacancy($vacancy, refresh: true);

        $this->assertCount(0, $fake->calls);
        $this->assertSame('rules', $matches[0]->source);
    }

    public function test_concurrent_ai_request_for_same_user_falls_back_to_rules(): void
    {
        $this->activeNanny();
        $fake = $this->fake();
        $parent = $this->parentUser();
        $vacancy = $this->activeVacancy($parent);

        // Boshqa so'rov shu foydalanuvchi uchun AI chaqirmoqda (qulf band) — limitni chetlab o'tib bo'lmaydi.
        $lock = Cache::lock('ai-limit:matching:'.$parent->id, 120);
        $this->assertTrue($lock->get());

        $matches = app(MatchingService::class)->forVacancy($vacancy, refresh: true);
        $this->assertCount(0, $fake->calls);
        $this->assertSame('rules', $matches[0]->source);

        // Qulf bo'shagach — AI ishlatiladi, qulf qaytariladi.
        $lock->release();
        app(MatchingService::class)->forVacancy($vacancy, refresh: true);
        $this->assertCount(1, $fake->calls);
        $this->assertTrue(Cache::lock('ai-limit:matching:'.$parent->id, 1)->get());
    }

    public function test_results_are_cached_for_six_hours_and_refresh_recomputes(): void
    {
        $this->activeNanny();
        $fake = $this->fake();
        $service = app(MatchingService::class);
        $vacancy = $this->activeVacancy();

        $service->forVacancy($vacancy);
        $service->forVacancy($vacancy);
        $this->assertCount(1, $fake->calls);

        $service->forVacancy($vacancy, refresh: true);
        $this->assertCount(2, $fake->calls);

        $this->travel(7)->hours();
        $service->forVacancy($vacancy);
        $this->assertCount(3, $fake->calls);
        $this->assertSame(1, MatchResult::count()); // eski qatorlar almashtirildi
    }

    public function test_vacancy_matches_endpoint(): void
    {
        $this->fake();
        $nanny = $this->activeNanny();
        $parent = $this->parentUser();
        $vacancy = $this->activeVacancy($parent);

        Sanctum::actingAs($parent);
        $response = $this->getJson("/api/v1/my/vacancies/{$vacancy->id}/matches")->assertOk();
        $response->assertJsonPath('data.0.nanny.id', $nanny->id)
            ->assertJsonPath('data.0.score', 80)
            ->assertJsonPath('data.0.reason.uz', 'Mos keladi')
            ->assertJsonPath('data.0.reason.ru', 'Подходит')
            ->assertJsonPath('data.0.source', 'ai');

        $body = $response->getContent();
        $this->assertStringNotContainsString($nanny->user->telegram_username, $body);
        $this->assertStringNotContainsString('Karimova', $body); // faqat "Dilnoza K."
    }

    public function test_vacancy_matches_authorization(): void
    {
        $this->fake();
        $owner = $this->parentUser();
        $vacancy = $this->activeVacancy($owner);
        $pending = Vacancy::factory()->create(['parent_profile_id' => $owner->parentProfile->id, 'status' => VacancyStatus::Pending]);

        $this->getJson("/api/v1/my/vacancies/{$vacancy->id}/matches")->assertUnauthorized();

        Sanctum::actingAs($this->parentUser());
        $this->getJson("/api/v1/my/vacancies/{$vacancy->id}/matches")->assertForbidden();

        Sanctum::actingAs($this->nannyUser());
        $this->getJson("/api/v1/my/vacancies/{$vacancy->id}/matches")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->getJson("/api/v1/my/vacancies/{$pending->id}/matches")->assertStatus(409);
    }

    public function test_nanny_matches_endpoint(): void
    {
        $this->fake();
        $user = $this->nannyUser(['status' => NannyProfileStatus::Active, 'published_at' => now(), 'price_amount' => 3_500_000]);
        $vacancy = $this->activeVacancy();

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/my/nanny-profile/matches')->assertOk()
            ->assertJsonPath('data.0.vacancy.id', $vacancy->id)
            ->assertJsonPath('data.0.score', 80);

        $inactive = $this->nannyUser();
        Sanctum::actingAs($inactive);
        $this->getJson('/api/v1/my/nanny-profile/matches')->assertStatus(409);

        Sanctum::actingAs($this->parentUser());
        $this->getJson('/api/v1/my/nanny-profile/matches')->assertForbidden();
    }

    public function test_rules_ranker_is_used_without_api_key(): void
    {
        config(['services.anthropic.key' => null]);
        $this->assertInstanceOf(RuleBasedMatchRanker::class, app(MatchRanker::class));

        config(['services.anthropic.key' => 'sk-test', 'services.anthropic.enabled' => true]);
        $this->assertInstanceOf(ClaudeMatchRanker::class, app(MatchRanker::class));

        config(['services.anthropic.enabled' => false]);
        $this->assertInstanceOf(RuleBasedMatchRanker::class, app(MatchRanker::class));
    }

    public function test_rules_prefer_same_district_and_price_fit(): void
    {
        config(['services.anthropic.key' => null]);
        $district = District::where('region_id', Region::value('id'))->first();
        $good = $this->activeNanny(['district_id' => $district->id, 'price_amount' => 3_500_000, 'experience_years' => 5]);
        $weak = $this->activeNanny(['district_id' => null, 'price_amount' => 4_700_000, 'experience_years' => 0, 'languages' => ['uz']]);
        $vacancy = $this->activeVacancy(null, ['district_id' => $district->id]);

        $matches = app(MatchingService::class)->forVacancy($vacancy);

        $this->assertSame([$good->id, $weak->id], $matches->pluck('nanny_profile_id')->all());
        $this->assertSame('rules', $matches[0]->source);
        $this->assertGreaterThan($matches[1]->score, $matches[0]->score);
        $this->assertLessThanOrEqual(140, mb_strlen($matches[0]->reason_uz));
    }

    public function test_job_is_dispatched_on_publish_and_computes(): void
    {
        Queue::fake();
        $vacancy = Vacancy::factory()->create(['parent_profile_id' => $this->parentUser()->parentProfile->id]);

        $vacancy->publish();

        Queue::assertPushed(ComputeMatches::class, fn ($job) => $job->type === 'vacancy' && $job->id === $vacancy->id);
    }

    public function test_job_stores_matches(): void
    {
        $this->fake();
        $this->activeNanny();
        $vacancy = $this->activeVacancy();

        (new ComputeMatches('vacancy', $vacancy->id))->handle(app(MatchingService::class));

        $this->assertSame(1, MatchResult::where('vacancy_id', $vacancy->id)->count());
        $this->assertSame('vacancy-'.$vacancy->id, (new ComputeMatches('vacancy', $vacancy->id))->uniqueId());
    }
}
