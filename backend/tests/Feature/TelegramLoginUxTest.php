<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Kirish oqimi qulayligi: raqam o'rniga matn/ovoz, kod xabaridagi "saytga qaytish" tugmasi. */
class TelegramLoginUxTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-secret';

    private const TG_ID = 777000222;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        config([
            'services.telegram.bot_token' => '123:abc',
            'services.telegram.webhook_secret' => self::SECRET,
            'app.frontend_url' => 'https://enagalar.test',
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function webhook(array $message): void
    {
        $message += [
            'message_id' => 1,
            'from' => ['id' => self::TG_ID, 'is_bot' => false, 'first_name' => 'Aziza', 'language_code' => 'uz'],
            'chat' => ['id' => self::TG_ID, 'type' => 'private'],
        ];

        $this->postJson('/api/v1/telegram/webhook', ['update_id' => 1, 'message' => $message], [
            'X-Telegram-Bot-Api-Secret-Token' => self::SECRET,
        ])->assertOk();
    }

    public function test_voice_or_text_instead_of_contact_repeats_the_contact_button(): void
    {
        $token = $this->postJson('/api/v1/auth/telegram/start')->assertCreated()->json('token');
        $this->webhook(['text' => "/start login_{$token}"]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->webhook(['voice' => ['file_id' => 'x', 'duration' => 2]]);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'sendMessage')
            && str_contains(json_encode($r['reply_markup'] ?? []), 'request_contact'));
    }

    public function test_text_without_pending_login_is_ignored(): void
    {
        $this->webhook(['text' => 'salom']);

        Http::assertNothingSent();
    }

    public function test_code_message_has_button_back_to_site_with_token(): void
    {
        $token = $this->postJson('/api/v1/auth/telegram/start')->assertCreated()->json('token');
        $this->webhook(['text' => "/start login_{$token}"]);
        $this->webhook(['contact' => ['phone_number' => '998901112233', 'first_name' => 'Aziza', 'user_id' => self::TG_ID]]);

        Http::assertSent(fn ($r) => str_contains($r['text'] ?? '', 'Kirish kodi')
            && str_contains(json_encode($r['reply_markup'] ?? []), 'enagalar.test\/kirish?t='.$token));
        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertJsonPath('status', 'awaiting_code');
    }
}
