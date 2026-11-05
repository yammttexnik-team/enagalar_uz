<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramWidgetLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['services.telegram.bot_token' => '123456:TEST-token']);
    }

    /** Telegram vidjeti kabi imzolangan ma'lumot. */
    private function signed(array $fields): array
    {
        ksort($fields);
        $check = implode("\n", array_map(fn ($k, $v) => "$k=$v", array_keys($fields), $fields));

        return $fields + ['hash' => hash_hmac('sha256', $check, hash('sha256', '123456:TEST-token', true))];
    }

    public function test_new_user_logs_in_with_valid_signature(): void
    {
        $body = $this->signed(['id' => 777, 'first_name' => 'Malika', 'username' => 'malika_ona', 'auth_date' => now()->timestamp]);

        $this->postJson('/api/v1/auth/telegram/widget', $body + ['campaign' => 'CLAIM'])
            ->assertOk()->assertJsonPath('status', 'approved')->assertJsonStructure(['access_token', 'user']);

        $user = User::where('telegram_id', 777)->sole();
        $this->assertSame('malika_ona', $user->telegram_username);
        $this->assertSame('CLAIM', $user->campaign?->code);
        $this->assertNull($user->phone);
    }

    public function test_existing_user_is_matched_by_telegram_id(): void
    {
        $user = User::factory()->create(['telegram_id' => 555, 'phone' => '+998901112233']);
        $body = $this->signed(['id' => 555, 'first_name' => 'X', 'auth_date' => now()->timestamp]);

        $this->postJson('/api/v1/auth/telegram/widget', $body)->assertOk();
        $this->assertSame(1, User::where('telegram_id', 555)->count());
        $this->assertSame('+998901112233', $user->fresh()->phone);
    }

    public function test_forged_or_old_data_is_rejected(): void
    {
        $body = $this->signed(['id' => 777, 'first_name' => 'Malika', 'auth_date' => now()->timestamp]);
        $this->postJson('/api/v1/auth/telegram/widget', ['id' => 778] + $body)->assertStatus(422);

        $old = $this->signed(['id' => 777, 'auth_date' => now()->subDays(2)->timestamp]);
        $this->postJson('/api/v1/auth/telegram/widget', $old)->assertStatus(422);
        $this->assertSame(0, User::where('telegram_id', 777)->count());
    }
}
