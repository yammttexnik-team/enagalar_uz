<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\User;
use App\Services\Telegram\TelegramBot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

        // Qabul qiluvchi xabarni saytda allaqachon o'qigan bo'lsa (chat ochiq edi) — bildirishnoma kerak emas.
        $lastRead = (int) DB::table('conversation_participants')
            ->where('conversation_id', $message->conversation_id)
            ->where('user_id', $recipient->id)
            ->value('last_read_message_id');
        if ($lastRead >= $message->id) {
            // Yuborilmadi — keyingi xabar uchun 10 daqiqalik cheklovni bo'shatamiz.
            Cache::forget("chat:tg-notified:{$message->conversation_id}:{$recipient->id}");

            return;
        }

        $title = $recipient->locale === 'ru' ? '💬 Новое сообщение' : '💬 Yangi xabar';
        $url = rtrim((string) config('app.frontend_url'), '/').'/kabinet/xabarlar/'.$message->conversation_id;

        // Xom havola o'rniga tugma: chiroyliroq va bosish oson
        $bot->sendMessage((int) $recipient->telegram_id, implode("\n", [
            $title.': '.e($message->sender?->publicName() ?? ''),
            e($message->preview()),
        ]), [
            'inline_keyboard' => [[['text' => $recipient->locale === 'ru' ? 'Ответить' : 'Javob berish', 'url' => $url]]],
        ]);
    }
}
