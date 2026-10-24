<?php

namespace Tests\Unit;

use App\Services\Telegram\TelegramBot;
use Tests\TestCase;

class TelegramBotRedactTest extends TestCase
{
    public function test_bot_token_is_removed_from_error_text(): void
    {
        config(['services.telegram.bot_token' => '123456:ABC-def_789']);

        $text = TelegramBot::redact('cURL error 28 for https://api.telegram.org/bot123456:ABC-def_789/sendMessage and bot999:XYZ');

        $this->assertStringNotContainsString('ABC-def_789', $text);
        $this->assertStringNotContainsString('XYZ', $text);
    }
}
