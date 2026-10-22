<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
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

    /** Bot oxirgi yuborgan kirish kodi. */
    private function codeFromBot(): string
    {
        $codes = Http::recorded(fn (Request $request) => str_contains($request->url(), 'sendMessage'))
            ->map(fn (array $pair) => preg_match('/<code>(\d{6})<\/code>/', $pair[0]['text'] ?? '', $m) ? $m[1] : null)
            ->filter();

        $this->assertNotEmpty($codes, 'Bot kirish kodini yubormadi.');

        return $codes->last();
    }

    private function confirm(string $token, string $code): TestResponse
    {
        return $this->postJson("/api/v1/auth/telegram/status/{$token}/confirm", ['code' => $code]);
    }

    private function wrongCode(string $code): string
    {
        return str_pad((string) (((int) $code + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    public function test_full_login_flow_via_bot(): void
    {
        $token = $this->startLogin();

        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertJson(['status' => 'pending']);

        $this->webhook(['text' => "/start login_{$token}"]);
        $this->webhook(['contact' => ['phone_number' => '998901234567', 'first_name' => 'Dilnoza', 'user_id' => self::TG_ID]]);

        // Polling token bermaydi, faqat kod kutilayotganini aytadi.
        $this->getJson("/api/v1/auth/telegram/status/{$token}")
            ->assertOk()
            ->assertExactJson(['status' => 'awaiting_code']);

        $response = $this->confirm($token, $this->codeFromBot())
            ->assertOk()
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('user.phone', '+998901234567')
            ->assertJsonPath('user.badges.0.slug', 'phone-verified');

        // So'rov bir marta ishlatiladi.
        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertStatus(410);
        $this->confirm($token, $this->codeFromBot())->assertStatus(410);

        $this->withToken($response->json('access_token'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.name', 'Dilnoza');
    }

    public function test_returning_user_is_approved_without_sharing_contact_again(): void
    {
        $user = User::factory()->create(['telegram_id' => self::TG_ID]);
        $token = $this->startLogin();

        $this->webhook(['text' => "/start login_{$token}"]);

        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertJson(['status' => 'awaiting_code']);

        $this->confirm($token, $this->codeFromBot())
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    /** Hujumchi o'z so'rovining havolasini qurbonga yuboradi: qurbon Start bossa ham hujumchi token ololmaydi. */
    public function test_attacker_cannot_get_token_by_sending_link_to_victim(): void
    {
        User::factory()->create(['telegram_id' => self::TG_ID]);
        $attackerToken = $this->startLogin();

        $this->webhook(['text' => "/start login_{$attackerToken}"]);

        $this->getJson("/api/v1/auth/telegram/status/{$attackerToken}")
            ->assertOk()
            ->assertJsonMissingPath('access_token');

        $this->confirm($attackerToken, $this->wrongCode($this->codeFromBot()))
            ->assertUnprocessable()
            ->assertJsonMissingPath('access_token');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_code_cannot_be_confirmed_before_bot_approval(): void
    {
        $token = $this->startLogin();

        $this->confirm($token, '123456')->assertStatus(409)->assertJson(['status' => 'pending']);
        $this->confirm($token, 'abc')->assertUnprocessable();
    }

    public function test_too_many_wrong_codes_kill_the_request(): void
    {
        User::factory()->create(['telegram_id' => self::TG_ID]);
        $token = $this->startLogin();
        $this->webhook(['text' => "/start login_{$token}"]);
        $code = $this->codeFromBot();

        for ($i = 1; $i < 5; $i++) {
            $this->confirm($token, $this->wrongCode($code))
                ->assertUnprocessable()
                ->assertJsonPath('attempts_left', 5 - $i);
        }

        $this->confirm($token, $this->wrongCode($code))->assertStatus(410);

        // To'g'ri kod ham endi ishlamaydi.
        $this->confirm($token, $code)->assertStatus(410);
        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertStatus(410);
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

    public function test_blocked_user_cannot_log_in(): void
    {
        User::factory()->create(['telegram_id' => self::TG_ID, 'status' => UserStatus::Blocked]);
        $token = $this->startLogin();

        $this->webhook(['text' => "/start login_{$token}"]);

        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertJson(['status' => 'pending']);
        Http::assertSent(fn ($request) => str_contains($request['text'] ?? '', 'bloklangan'));
        Http::assertNotSent(fn ($request) => str_contains($request['text'] ?? '', '<code>'));
    }

    public function test_blocked_user_found_by_phone_cannot_log_in(): void
    {
        User::factory()->create(['phone' => '+998901234567', 'status' => UserStatus::Blocked]);
        $token = $this->startLogin();

        $this->webhook(['text' => "/start login_{$token}"]);
        $this->webhook(['contact' => ['phone_number' => '998901234567', 'first_name' => 'Dilnoza', 'user_id' => self::TG_ID]]);

        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertJson(['status' => 'pending']);
        Http::assertNotSent(fn ($request) => str_contains($request['text'] ?? '', '<code>'));
    }

    public function test_user_blocked_after_code_was_sent_cannot_finish_login(): void
    {
        $user = User::factory()->create(['telegram_id' => self::TG_ID]);
        $token = $this->startLogin();
        $this->webhook(['text' => "/start login_{$token}"]);

        $user->update(['status' => UserStatus::Blocked]);

        $this->confirm($token, $this->codeFromBot())->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_blocking_revokes_tokens_and_closes_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me')->assertOk();

        $user->update(['status' => UserStatus::Blocked]);
        $this->assertSame(0, $user->tokens()->count());

        // Token qolib ketgan bo'lsa ham (masalan, bazada to'g'ridan-to'g'ri bloklangan) API yopiq.
        $other = User::factory()->create();
        $otherToken = $other->createToken('web')->plainTextToken;
        User::whereKey($other->id)->update(['status' => UserStatus::Blocked->value]);

        $this->app['auth']->forgetGuards();
        $this->withToken($otherToken)->getJson('/api/v1/me')->assertForbidden();
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
