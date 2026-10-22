<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramLoginTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-secret';

    private const TG_ID = 555000111;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        config([
            'services.telegram.bot_token' => '123:abc',
            'services.telegram.bot_username' => 'enagalar_bot',
            'services.telegram.webhook_secret' => self::SECRET,
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function webhook(array $message): void
    {
        $message += [
            'message_id' => 1,
            'from' => ['id' => self::TG_ID, 'is_bot' => false, 'first_name' => 'Dilnoza', 'username' => 'dilnoza', 'language_code' => 'uz'],
            'chat' => ['id' => self::TG_ID, 'type' => 'private'],
        ];

        $this->postJson('/api/v1/telegram/webhook', ['update_id' => 1, 'message' => $message], [
            'X-Telegram-Bot-Api-Secret-Token' => self::SECRET,
        ])->assertOk();
    }

    private function startLogin(): string
    {
        $response = $this->postJson('/api/v1/auth/telegram/start')->assertCreated();
        $token = $response->json('token');

        $this->assertSame("https://t.me/enagalar_bot?start=login_{$token}", $response->json('bot_url'));

        return $token;
    }

    public function test_full_login_flow_via_bot(): void
    {
        $token = $this->startLogin();

        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertJson(['status' => 'pending']);

        $this->webhook(['text' => "/start login_{$token}"]);
        $this->webhook(['contact' => ['phone_number' => '998901234567', 'first_name' => 'Dilnoza', 'user_id' => self::TG_ID]]);

        $response = $this->getJson("/api/v1/auth/telegram/status/{$token}")
            ->assertOk()
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('user.phone', '+998901234567')
            ->assertJsonPath('user.badges.0.slug', 'phone-verified');

        // Token bir marta ishlatiladi.
        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertStatus(410);

        $this->withToken($response->json('access_token'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.name', 'Dilnoza');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'], 'tasdiqlandi'));
    }

    public function test_returning_user_is_approved_without_sharing_contact_again(): void
    {
        $user = User::factory()->create(['telegram_id' => self::TG_ID]);
        $token = $this->startLogin();

        $this->webhook(['text' => "/start login_{$token}"]);

        $this->getJson("/api/v1/auth/telegram/status/{$token}")
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_someone_elses_contact_is_rejected(): void
    {
        $token = $this->startLogin();

        $this->webhook(['text' => "/start login_{$token}"]);
        $this->webhook(['contact' => ['phone_number' => '998901112233', 'first_name' => 'Boshqa', 'user_id' => 999]]);

        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertJson(['status' => 'pending']);
        $this->assertDatabaseMissing('users', ['phone' => '+998901112233']);
    }

    public function test_expired_request_cannot_be_used(): void
    {
        $token = $this->startLogin();
        $this->travel(11)->minutes();

        $this->webhook(['text' => "/start login_{$token}"]);

        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertStatus(410);
    }

    public function test_webhook_rejects_wrong_secret(): void
    {
        $this->postJson('/api/v1/telegram/webhook', ['update_id' => 1], [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong',
        ])->assertForbidden();
    }

    public function test_user_can_add_nanny_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/me/roles', ['role' => 'nanny'])
            ->assertOk()
            ->assertJsonPath('data.roles', ['nanny']);

        $this->assertNotNull($user->fresh()->nannyProfile);

        $this->actingAs($user)->postJson('/api/v1/me/roles', ['role' => 'admin'])->assertUnprocessable();
    }
}
