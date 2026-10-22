<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('telegram:set-webhook')]
#[Description('Telegram botga webhook manzilini o\'rnatadi')]
class TelegramSetWebhook extends Command
{
    public function handle(TelegramBot $bot): int
    {
        $url = url('/api/v1/telegram/webhook');
        $response = $bot->setWebhook($url, (string) config('services.telegram.webhook_secret'));

        $this->line($response->body());

        return $response->json('ok') ? self::SUCCESS : self::FAILURE;
    }
}
