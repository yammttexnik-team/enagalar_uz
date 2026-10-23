<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Jobs\SendBadgeConsentNotification;
use App\Models\Badge;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Services\Telegram\TelegramBot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

/** Nishonni profilda ko'rsatish roziligi: admin bergan nishon foydalanuvchi javobigacha yashirin turadi. */
class BadgeConsentTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::factory()->create()->assignRole(Role::Admin->value);
        $this->user = $this->nannyUser(['status' => 'active']);
    }

    private function badge(string $slug = Badge::MEDICAL): Badge
    {
        return Badge::where('slug', $slug)->firstOrFail();
    }

    /** Ochiq enaga API'sidagi nishon slug'lari. */
    private function publicSlugs(): array
    {
        $res = $this->getJson('/api/v1/nannies/'.$this->user->nannyProfile->id)->assertOk();

        return collect($res->json('data.badges'))->pluck('slug')->all();
    }

    private function myBadge(string $slug): ?array
    {
        return collect($this->getJson('/api/v1/my/verification')->assertOk()->json('data.badges'))->firstWhere('slug', $slug);
    }

    public function test_admin_grant_is_hidden_until_user_answers_and_dispatches_job(): void
    {
        Queue::fake();
        $badge = $this->badge();

        $badge->grantTo($this->user, $this->admin);

        $this->assertNotContains($badge->slug, $this->publicSlugs());
        Queue::assertPushed(SendBadgeConsentNotification::class, fn ($job) => $job->userId === $this->user->id && $job->badgeId === $badge->id);

        Sanctum::actingAs($this->user);
        $mine = $this->myBadge($badge->slug);
        $this->assertNotNull($mine);
        $this->assertNull($mine['is_public']);
    }

    public function test_user_can_show_badge_publicly(): void
    {
        Queue::fake();
        $badge = $this->badge();
        $badge->grantTo($this->user, $this->admin);

        Sanctum::actingAs($this->user);
        $this->patchJson("/api/v1/my/badges/{$badge->slug}", ['is_public' => true])
            ->assertOk()->assertJsonPath('data.is_public', true);

        $this->assertContains($badge->slug, $this->publicSlugs());
        $this->assertNotNull($this->user->badges()->first()->pivot->answered_at);
    }

    public function test_user_can_hide_badge(): void
    {
        Queue::fake();
        $badge = $this->badge();
        $badge->grantTo($this->user, $this->admin);

        Sanctum::actingAs($this->user);
        $this->patchJson("/api/v1/my/badges/{$badge->slug}", ['is_public' => true])->assertOk();
        $this->patchJson("/api/v1/my/badges/{$badge->slug}", ['is_public' => false])
            ->assertOk()->assertJsonPath('data.is_public', false);

        $this->assertNotContains($badge->slug, $this->publicSlugs());
        // Yashirilgan nishon foydalanuvchining o'z ro'yxatida qoladi
        $this->assertNotNull($this->myBadge($badge->slug));
    }

    public function test_patch_requires_auth_and_boolean(): void
    {
        Queue::fake();
        $badge = $this->badge();
        $this->patchJson("/api/v1/my/badges/{$badge->slug}", ['is_public' => true])->assertUnauthorized();

        $badge->grantTo($this->user, $this->admin);
        Sanctum::actingAs($this->user);
        $this->patchJson("/api/v1/my/badges/{$badge->slug}", [])->assertUnprocessable();
    }

    public function test_patch_for_badge_user_does_not_have_is_404(): void
    {
        Sanctum::actingAs($this->user);

        $this->patchJson('/api/v1/my/badges/'.Badge::MEDICAL, ['is_public' => true])->assertNotFound();
        $this->patchJson('/api/v1/my/badges/nope', ['is_public' => true])->assertNotFound();
    }

    public function test_expired_badge_cannot_be_answered(): void
    {
        Queue::fake();
        $badge = $this->badge();
        $badge->grantTo($this->user, $this->admin);
        $this->user->badges()->updateExistingPivot($badge->id, ['expires_at' => now()->subDay()]);

        Sanctum::actingAs($this->user);
        $this->patchJson("/api/v1/my/badges/{$badge->slug}", ['is_public' => true])->assertNotFound();
    }

    public function test_system_grant_is_public_immediately_without_job(): void
    {
        Queue::fake();
        $badge = $this->badge(Badge::PHONE_VERIFIED);

        $badge->grantTo($this->user);

        $this->assertContains($badge->slug, $this->publicSlugs());
        Queue::assertNotPushed(SendBadgeConsentNotification::class);
    }

    public function test_regrant_keeps_previous_answer_and_does_not_notify_again(): void
    {
        Queue::fake();
        $badge = $this->badge();
        $badge->grantTo($this->user, $this->admin);

        Sanctum::actingAs($this->user);
        $this->patchJson("/api/v1/my/badges/{$badge->slug}", ['is_public' => false])->assertOk();

        $badge->grantTo($this->user, $this->admin);

        $this->assertFalse($this->user->badges()->first()->pivot->is_public);
        $this->assertNotContains($badge->slug, $this->publicSlugs());
        Queue::assertPushed(SendBadgeConsentNotification::class, 1);
    }

    public function test_identity_approval_makes_user_verified_but_badge_pending_consent(): void
    {
        Queue::fake();
        $verification = IdentityVerification::create([
            'user_id' => $this->user->id,
            'full_name' => 'Karimova Dilnoza',
            'passport_series' => 'AD',
            'passport_number' => '1234567',
            'pinfl' => '41234567890123',
            'birth_date' => '1990-05-12',
        ]);

        $verification->approve($this->admin);

        $this->assertTrue($this->user->fresh()->isVerified());
        $this->assertNotContains(Badge::VERIFIED, $this->publicSlugs());
        Queue::assertPushed(SendBadgeConsentNotification::class);

        Sanctum::actingAs($this->user);
        $this->assertNull($this->myBadge(Badge::VERIFIED)['is_public']);
        $this->getJson('/api/v1/my/verification')->assertJsonPath('data.identity.verified', true);
    }

    public function test_filament_multiselect_attach_dispatches_job(): void
    {
        Queue::fake();
        $badge = $this->badge();

        // UserForm'dagi Select::relationship()->pivotData() is_public'siz yozadi
        $this->user->badges()->attach($badge->id, ['granted_at' => now(), 'granted_by' => $this->admin->id]);

        Queue::assertPushed(SendBadgeConsentNotification::class, 1);
        $this->assertNotContains($badge->slug, $this->publicSlugs());
    }

    public function test_job_sends_telegram_message_with_url_button(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        config(['services.telegram.bot_token' => '123:abc', 'app.frontend_url' => 'https://enagalar.uz']);
        $this->user->update(['telegram_id' => 777, 'locale' => 'uz']);
        $badge = $this->badge();

        (new SendBadgeConsentNotification($this->user->id, $badge->id))->handle(app(TelegramBot::class));

        Http::assertSent(function (Request $request) use ($badge) {
            $button = $request['reply_markup']['inline_keyboard'][0][0] ?? [];

            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] === 777
                && str_contains($request['text'], e($badge->name_uz))
                && ($button['url'] ?? null) === 'https://enagalar.uz/kabinet/tasdiqlash';
        });
    }

    public function test_job_skips_without_telegram_id_or_token(): void
    {
        Http::fake();
        $badge = $this->badge();

        config(['services.telegram.bot_token' => '123:abc']);
        (new SendBadgeConsentNotification($this->user->id, $badge->id))->handle(app(TelegramBot::class));

        $this->user->update(['telegram_id' => 777]);
        config(['services.telegram.bot_token' => '']);
        (new SendBadgeConsentNotification($this->user->id, $badge->id))->handle(app(TelegramBot::class));

        Http::assertNothingSent();
    }
}
