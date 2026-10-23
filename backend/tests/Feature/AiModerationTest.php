<?php

namespace Tests\Feature;

use App\Enums\NannyProfileStatus;
use App\Enums\Role;
use App\Enums\VacancyStatus;
use App\Jobs\ReviewListingWithAi;
use App\Models\AiRequest;
use App\Models\NannyProfile;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Ai\ModerationReviewer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiModerationTest extends TestCase
{
    use RefreshDatabase;

    public int $calls = 0;

    /** @var array<int, string> */
    public array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['services.anthropic.key' => 'sk-test', 'services.anthropic.enabled' => true]);
    }

    private function fakeReviewer(array $response): void
    {
        $test = $this;
        $this->app->instance(ModerationReviewer::class, new class($response, $test) extends ModerationReviewer
        {
            public function __construct(private readonly array $response, private readonly object $test) {}

            protected function callApi(string $system, string $userContent, string $model, array $schema): array
            {
                $this->test->calls++;
                $this->test->sent[] = $userContent;

                return ['text' => json_encode($this->response, JSON_UNESCAPED_UNICODE), 'stop_reason' => 'end_turn', 'input_tokens' => 200, 'output_tokens' => 50];
            }
        });
    }

    private function vacancy(array $attrs = []): Vacancy
    {
        // Yaratilganda sinxron job ishlamasin: AI vaqtincha o'chiriladi.
        config(['services.anthropic.enabled' => false]);
        $vacancy = Vacancy::factory()->create($attrs);
        config(['services.anthropic.enabled' => true]);

        return $vacancy;
    }

    public function test_reviewer_stores_result_and_logs_request(): void
    {
        $this->fakeReviewer(['risk' => 8, 'verdict' => 'ok', 'flags' => [], 'summary' => 'Oddiy, to\'g\'ri e\'lon.']);
        $vacancy = $this->vacancy();

        $this->assertTrue(app(ModerationReviewer::class)->review($vacancy));

        $vacancy->refresh();
        $this->assertSame(8, $vacancy->ai_risk);
        $this->assertSame('ok', $vacancy->ai_verdict->value);
        $this->assertSame([], $vacancy->ai_flags);
        $this->assertNotNull($vacancy->ai_checked_at);
        $this->assertSame(VacancyStatus::Pending, $vacancy->status);   // AI statusni o'zgartirmaydi
        $this->assertDatabaseHas('ai_requests', [
            'feature' => 'moderation', 'user_id' => $vacancy->parentProfile->user_id, 'status' => 'ok',
        ]);
    }

    public function test_contact_in_text_forces_check_and_is_not_sent_to_claude(): void
    {
        $this->fakeReviewer(['risk' => 3, 'verdict' => 'ok', 'flags' => [], 'summary' => '']);
        $vacancy = $this->vacancy(['description' => 'Enaga kerak, yozing @secret_user yoki +998 90 123 45 67']);

        app(ModerationReviewer::class)->review($vacancy);

        $vacancy->refresh();
        $this->assertSame('check', $vacancy->ai_verdict->value);
        $this->assertContains('contact_in_text', $vacancy->ai_flags);
        $this->assertGreaterThanOrEqual(30, $vacancy->ai_risk);
        $this->assertStringNotContainsString('secret_user', $this->sent[0]);
        $this->assertStringNotContainsString('123 45 67', $this->sent[0]);
    }

    public function test_nanny_profile_is_reviewed_without_passport_data(): void
    {
        $this->fakeReviewer(['risk' => 85, 'verdict' => 'bad', 'flags' => ['scam_or_prepayment', 'bogus'], 'summary' => 'Oldindan to\'lov so\'ralgan.']);
        Queue::fake();
        $user = User::factory()->create()->assignRole(Role::Nanny->value);
        $profile = NannyProfile::factory()->create(['user_id' => $user->id, 'bio' => 'Oldindan pul yuboring']);

        app(ModerationReviewer::class)->review($profile);

        $profile->refresh();
        $this->assertSame('bad', $profile->ai_verdict->value);
        $this->assertSame(['scam_or_prepayment'], $profile->ai_flags);
        $this->assertStringNotContainsString('passport', strtolower($this->sent[0]));
    }

    public function test_observer_dispatches_job_when_pending(): void
    {
        Queue::fake();

        $vacancy = Vacancy::factory()->create();
        Queue::assertPushed(ReviewListingWithAi::class, fn ($j) => $j->type === 'vacancy' && $j->id === $vacancy->id);

        $nanny = NannyProfile::factory()->create(['status' => NannyProfileStatus::Pending]);
        Queue::assertPushed(ReviewListingWithAi::class, fn ($j) => $j->type === 'nanny' && $j->id === $nanny->id);
    }

    public function test_observer_does_not_dispatch_for_active_listing(): void
    {
        Queue::fake();

        Vacancy::factory()->active()->create();

        Queue::assertNotPushed(ReviewListingWithAi::class);
    }

    public function test_disabled_ai_dispatches_nothing_and_makes_no_call(): void
    {
        config(['services.anthropic.enabled' => false]);
        $this->fakeReviewer(['risk' => 1, 'verdict' => 'ok', 'flags' => [], 'summary' => '']);
        Queue::fake();

        $vacancy = Vacancy::factory()->create();

        Queue::assertNotPushed(ReviewListingWithAi::class);
        $this->assertFalse(app(ModerationReviewer::class)->review($vacancy));
        $this->assertSame(0, $this->calls);
        $this->assertSame(0, AiRequest::where('feature', 'moderation')->count());
    }

    public function test_api_failure_leaves_listing_unchanged(): void
    {
        $this->app->instance(ModerationReviewer::class, new class extends ModerationReviewer
        {
            protected function callApi(string $system, string $userContent, string $model, array $schema): array
            {
                throw new \RuntimeException('boom');
            }
        });
        $vacancy = $this->vacancy();

        $this->assertFalse(app(ModerationReviewer::class)->review($vacancy));
        $this->assertNull($vacancy->refresh()->ai_verdict);
        $this->assertDatabaseHas('ai_requests', ['feature' => 'moderation', 'status' => 'error']);
    }

    public function test_admin_lists_and_view_pages_render_with_ai_data(): void
    {
        $admin = User::factory()->create()->assignRole(Role::Admin->value);
        $vacancy = $this->vacancy();
        $vacancy->forceFill(['ai_risk' => 75, 'ai_verdict' => 'bad', 'ai_flags' => ['offensive'], 'ai_summary' => 'Haqoratli matn.', 'ai_checked_at' => now()])->saveQuietly();
        Queue::fake();
        $nanny = NannyProfile::factory()->create();

        $this->actingAs($admin);
        foreach (['/admin/vacancies', '/admin/nanny-profiles', "/admin/vacancies/{$vacancy->id}", "/admin/nanny-profiles/{$nanny->id}", '/admin'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/vacancies')->assertSee('Shubhali');
    }
}
