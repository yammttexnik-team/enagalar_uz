<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\TelegramLoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampaignAttributionTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-secret';

    private const TG_ID = 777000111;

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

    private function webhook(array $message, int $tgId = self::TG_ID, string $lang = 'uz'): void
    {
        $message += [
            'message_id' => 1,
            'from' => ['id' => $tgId, 'is_bot' => false, 'first_name' => 'Dilnoza', 'language_code' => $lang],
            'chat' => ['id' => $tgId, 'type' => 'private'],
        ];

        $this->postJson('/api/v1/telegram/webhook', ['update_id' => 1, 'message' => $message], [
            'X-Telegram-Bot-Api-Secret-Token' => self::SECRET,
        ])->assertOk();
    }

    private function login(array $body, int $tgId = self::TG_ID, string $phone = '998901234567'): User
    {
        $token = $this->postJson('/api/v1/auth/telegram/start', $body)->assertCreated()->json('token');
        $this->webhook(['text' => "/start login_{$token}"], $tgId);
        $this->webhook(['contact' => ['phone_number' => $phone, 'first_name' => 'Dilnoza', 'user_id' => $tgId]], $tgId);

        $this->getJson("/api/v1/auth/telegram/status/{$token}")->assertJsonPath('status', 'awaiting_code');

        return User::where('telegram_id', $tgId)->firstOrFail();
    }

    public function test_campaign_code_is_stored_and_sets_campaign_on_first_login(): void
    {
        $campaign = Campaign::where('code', 'SURVEY_NANNY')->firstOrFail();

        $this->postJson('/api/v1/auth/telegram/start', ['campaign' => 'survey_nanny']);
        $this->assertSame('SURVEY_NANNY', TelegramLoginRequest::latest('id')->first()->campaign_code);

        $user = $this->login(['campaign' => 'survey_nanny']);

        $this->assertSame($campaign->id, $user->campaign_id);
        $this->assertNull($user->referred_by_id);
    }

    public function test_invalid_unknown_or_inactive_campaign_is_ignored(): void
    {
        Campaign::create(['code' => 'OFF', 'name' => 'Off', 'is_active' => false]);

        $this->assertNull($this->login(['campaign' => 'OFF'], 1001, '998900000001')->campaign_id);
        $this->assertNull($this->login(['campaign' => 'NOPE'], 1002, '998900000002')->campaign_id);

        $this->postJson('/api/v1/auth/telegram/start', ['campaign' => '<script>'])->assertCreated();
        $this->assertNull(TelegramLoginRequest::latest('id')->first()->campaign_code);
    }

    public function test_campaign_is_not_overwritten_for_existing_user(): void
    {
        $first = Campaign::where('code', 'SURVEY_NANNY')->first();
        $this->login(['campaign' => 'SURVEY_NANNY']);

        $user = $this->login(['campaign' => 'FRIENDS']);

        $this->assertSame($first->id, $user->campaign_id);
    }

    public function test_max_participants_limit(): void
    {
        $campaign = Campaign::create(['code' => 'LIMIT1', 'name' => 'Limit', 'max_participants' => 1]);

        $this->assertSame($campaign->id, $this->login(['campaign' => 'LIMIT1'], 2001, '998900000011')->campaign_id);
        $this->assertNull($this->login(['campaign' => 'LIMIT1'], 2002, '998900000012')->campaign_id);
    }

    public function test_referral_code_sets_referrer_on_creation(): void
    {
        $referrer = User::factory()->create();
        $code = $referrer->referralCode();
        $this->assertSame(8, strlen($code));

        $user = $this->login(['referral' => strtolower($code)]);

        $this->assertSame($referrer->id, $user->referred_by_id);
    }

    public function test_bot_campaign_start_replies_with_site_button(): void
    {
        $this->webhook(['text' => '/start c_SURVEY_NANNY']);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'sendMessage')
            && str_contains($r['text'], 'enagalar.uz')
            && str_contains(json_encode($r['reply_markup']), 'Saytga kirish')
            && str_contains(json_encode($r['reply_markup']), 'https:\/\/enagalar.test\/sorovnoma?c=SURVEY_NANNY'));
    }

    public function test_bot_campaign_start_in_russian(): void
    {
        $this->webhook(['text' => '/start c_FRIENDS'], lang: 'ru');

        Http::assertSent(fn ($r) => str_contains(json_encode($r['reply_markup']), 'enagalar.test\/ru\/kirish?c=FRIENDS'));
    }

    public function test_plain_start_and_login_flow_still_work(): void
    {
        $this->webhook(['text' => '/start']);
        Http::assertSent(fn ($r) => str_contains($r['text'] ?? '', 'ishonchli'));

        $this->assertNotNull($this->login([])->id);
    }
}
