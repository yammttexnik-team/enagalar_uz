<?php

namespace App\Services\Telegram;

use App\Models\User;

/** Foydalanuvchiga botdan shaxsiy xabar yuboradi. Bot bloklangan (403) bo'lsa, xabarlarni o'chiradi. */
class UserNotifier
{
    public function __construct(private TelegramBot $bot) {}

    public function send(User $user, string $text, ?array $replyMarkup = null): bool
    {
        if (! $user->telegram_id) {
            return false;
        }

        try {
            $response = $this->bot->sendMessage($user->telegram_id, $text, $replyMarkup, true);
        } catch (\Throwable $e) {
            report(new \RuntimeException(TelegramBot::redact($e->getMessage())));

            return false;
        }

        if ($response->status() === 403) {
            $user->forceFill(['notify_telegram' => false])->save();

            return false;
        }

        return $response->successful() && (bool) $response->json('ok');
    }

    /** Telegram ~30 xabar/s: sekundiga 20 tadan oshmaslik uchun. */
    public function throttle(): void
    {
        if (! app()->runningUnitTests()) {
            usleep(50_000);
        }
    }

    public static function url(string $lang, string $path): string
    {
        return rtrim((string) config('app.frontend_url'), '/').($lang === 'ru' ? '/ru' : '').$path;
    }
}
