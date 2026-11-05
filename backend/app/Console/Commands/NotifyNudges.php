<?php

namespace App\Console\Commands;

use App\Enums\NannyProfileStatus;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Telegram\UserNotifier;
use Illuminate\Console\Command;

class NotifyNudges extends Command
{
    protected $signature = 'notify:nudges';

    protected $description = "1-3 kun oldin ro'yxatdan o'tib, anketa/vakansiya joylamaganlarga bir martalik eslatma yuboradi";

    public function handle(UserNotifier $notifier): int
    {
        $sent = 0;

        User::query()
            ->whereNotNull('telegram_id')
            ->where('notify_telegram', true)
            ->where('status', UserStatus::Active)
            ->whereNull('nudged_at')
            ->whereBetween('created_at', [now()->subDays(3), now()->subDay()])
            ->role(['nanny', 'parent'])
            ->with('nannyProfile')
            ->chunkById(200, function ($users) use ($notifier, &$sent) {
                foreach ($users as $user) {
                    $isNanny = $user->hasRole('nanny');
                    $needs = $isNanny
                        ? (! $user->nannyProfile || $user->nannyProfile->status !== NannyProfileStatus::Active || ! $user->nannyProfile->region_id)
                        : ! Vacancy::withoutGlobalScopes()->whereHas('parentProfile', fn ($q) => $q->where('user_id', $user->id))->exists();
                    if (! $needs) {
                        continue;
                    }

                    $lang = $user->locale === 'ru' ? 'ru' : 'uz';
                    $text = match ([$isNanny, $lang]) {
                        [true, 'ru'] => "Ваша анкета ещё не опубликована. Заполните её, чтобы семьи вас нашли.\n\n🎁 Первое объявление или анкета — бонус 30 000 сум",
                        [true, 'uz'] => "Anketangiz hali eʼlon qilinmagan. Uni toʻldiring — oilalar sizni topadi.\n\n🎁 Birinchi eʼlon yoki anketa — 30 000 soʻm bonus",
                        [false, 'ru'] => "Вы ещё не разместили объявление о поиске няни. Это займёт пару минут.\n\n🎁 Первое объявление или анкета — бонус 30 000 сум",
                        default => "Siz hali enaga qidirish eʼlonini joylamadingiz. Bu bir necha daqiqa oladi.\n\n🎁 Birinchi eʼlon yoki anketa — 30 000 soʻm bonus",
                    };
                    $button = $isNanny
                        ? ($lang === 'ru' ? 'Заполнить анкету' : 'Anketani toʻldirish')
                        : ($lang === 'ru' ? 'Разместить объявление' : 'Eʼlon joylash');
                    $path = $isNanny ? '/kabinet/anketa' : '/kabinet/vakansiyalar/yangi';

                    // Qayta yuborilmasligi uchun yuborishdan oldin belgilanadi (403 bo'lsa ham qayta urinilmaydi).
                    $user->forceFill(['nudged_at' => now()])->save();
                    if ($notifier->send($user, $text, ['inline_keyboard' => [[['text' => $button, 'url' => UserNotifier::url($lang, $path)]]]])) {
                        $sent++;
                    }
                    $notifier->throttle();
                }
            });

        $this->info("{$sent} ta eslatma yuborildi.");

        return self::SUCCESS;
    }
}
