<?php

namespace App\Jobs;

use App\Models\Badge;
use App\Models\User;
use App\Services\Telegram\TelegramBot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Admin nishon bergach foydalanuvchiga Telegram'da: "Ma'lumot tasdiqlandi, profilda ko'rsatilsinmi?" */
class SendBadgeConsentNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $userId, public int $badgeId) {}

    public function handle(TelegramBot $bot): void
    {
        $user = User::find($this->userId);
        $badge = Badge::find($this->badgeId);

        if (! $user?->telegram_id || ! $badge || blank(config('services.telegram.bot_token'))) {
            return;
        }

        $ru = $user->locale === 'ru';
        $url = rtrim((string) config('app.frontend_url'), '/').($ru ? '/ru' : '').'/kabinet/tasdiqlash';
        $name = e($ru ? ($badge->name_ru ?: $badge->name_uz) : $badge->name_uz);

        $text = $ru
            ? "✅ Подтверждено: <b>{$name}</b>.\nПоказывать это в вашем профиле?"
            : "✅ Tasdiqlandi: <b>{$name}</b>.\nBuni profilingizda ko'rsataylikmi?";

        $bot->sendMessage((int) $user->telegram_id, $text, [
            'inline_keyboard' => [[['text' => $ru ? 'Ответить на сайте' : 'Saytda javob berish', 'url' => $url]]],
        ]);
    }
}
