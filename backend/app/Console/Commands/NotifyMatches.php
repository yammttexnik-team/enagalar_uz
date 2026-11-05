<?php

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Telegram\MatchDigest;
use App\Services\Telegram\UserNotifier;
use Illuminate\Console\Command;

class NotifyMatches extends Command
{
    protected $signature = 'notify:matches';

    protected $description = "Foydalanuvchilarga hududidagi yangi e'lonlar haqida Telegram orqali xabar yuboradi";

    public function handle(MatchDigest $digest, UserNotifier $notifier): int
    {
        $sent = 0;

        User::query()
            ->whereNotNull('telegram_id')
            ->where('notify_telegram', true)
            ->where('status', UserStatus::Active)
            ->where(fn ($q) => $q->whereNull('last_match_notified_at')->orWhere('last_match_notified_at', '<=', now()->subHours(20)))
            ->role(['nanny', 'parent'])
            ->chunkById(200, function ($users) use ($digest, $notifier, &$sent) {
                foreach ($users as $user) {
                    $since = $user->last_match_notified_at ?? now()->subDay();
                    $data = $digest->build($user, $since);
                    if (! $data) {
                        continue;
                    }

                    if ($digest->send($user, $data)) {
                        $user->forceFill(['last_match_notified_at' => now()])->save();
                        $sent++;
                    }
                    $notifier->throttle();
                }
            });

        $this->info("{$sent} ta xabar yuborildi.");

        return self::SUCCESS;
    }
}
