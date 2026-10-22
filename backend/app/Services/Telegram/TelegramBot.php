<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** Telegram Bot API uchun yupqa mijoz. */
class TelegramBot
{
    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): Response
    {
        return $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $replyMarkup,
        ]));
    }

    public function setWebhook(string $url, string $secret): Response
    {
        return $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message'],
        ]);
    }

    private function call(string $method, array $payload): Response
    {
        return $this->http()->post($method, $payload);
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/')
            ->acceptJson()
            ->timeout(10);
    }
}
