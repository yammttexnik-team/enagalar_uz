<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\User;
use App\Services\Telegram\TelegramBot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Yangi xabar haqida Telegram orqali xabar beradi (navbat ishchisi kerak). */
class SendChatTelegramNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $messageId, public int $recipientId) {}

    public function handle(TelegramBot $bot): void
    {
        $message = Message::with('sender')->find($this->messageId);
        $recipient = User::find($this->recipientId);

        if (! $message || ! $recipient?->telegram_id || blank(config('services.telegram.bot_token'))) {
            return;
        }

        $title = $recipient->locale === 'ru' ? '💬 Новое сообщение' : '💬 Yangi xabar';
        $url = rtrim((string) config('app.frontend_url'), '/').'/kabinet/xabarlar/'.$message->conversation_id;

        $bot->sendMessage((int) $recipient->telegram_id, implode("\n", [
            $title.': '.e($message->sender?->publicName() ?? ''),
            e($message->preview()),
            $url,
        ]));
    }
}
