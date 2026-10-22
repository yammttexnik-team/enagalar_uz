<?php

namespace App\Services\Telegram;

use App\Models\Badge;
use App\Models\Campaign;
use App\Models\TelegramLoginRequest;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Botga kelgan xabarlarni kirish jarayoni bo'yicha qayta ishlaydi. */
class TelegramLoginHandler
{
    public function __construct(private TelegramBot $bot) {}

    public function handle(array $update): void
    {
        $message = $update['message'] ?? null;
        $from = $message['from'] ?? null;

        if (! $message || ! $from || ($from['is_bot'] ?? false) || ($message['chat']['type'] ?? null) !== 'private') {
            return;
        }

        $lang = ($from['language_code'] ?? 'uz') === 'ru' ? 'ru' : 'uz';

        if (isset($message['contact'])) {
            $this->handleContact($message, $from, $lang);

            return;
        }

        $text = trim($message['text'] ?? '');

        if (Str::startsWith($text, '/start')) {
            $payload = trim(Str::after($text, '/start'));
            $this->handleStart($from, $payload, $lang);
        }
    }

    private function handleStart(array $from, string $payload, string $lang): void
    {
        if (preg_match('/^(c|r)_([A-Za-z0-9_]+)$/', $payload, $m)) {
            $this->handleCampaignStart($from, $m[1], $m[2], $lang);

            return;
        }

        if (! Str::startsWith($payload, TelegramLoginRequest::START_PREFIX)) {
            $this->bot->sendMessage($from['id'], $this->t('welcome', $lang));

            return;
        }

        $token = Str::after($payload, TelegramLoginRequest::START_PREFIX);
        $request = TelegramLoginRequest::usable()->where('token', $token)->first();

        if (! $request) {
            $this->bot->sendMessage($from['id'], $this->t('expired', $lang));

            return;
        }

        $request->update(['telegram_id' => $from['id']]);

        // Telefon raqami bor foydalanuvchi qayta kontakt yubormaydi.
        $user = User::where('telegram_id', $from['id'])->whereNotNull('phone')->first();

        if ($user) {
            $request->approve($user);
            $this->bot->sendMessage($from['id'], $this->t('approved', $lang), ['remove_keyboard' => true]);

            return;
        }

        $this->bot->sendMessage($from['id'], $this->t('share_contact', $lang), [
            'keyboard' => [[['text' => $this->t('share_button', $lang), 'request_contact' => true]]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ]);
    }

    /** /start c_<KOD> (kampaniya) yoki r_<KOD> (taklif): salom + saytga kirish tugmasi. */
    private function handleCampaignStart(array $from, string $kind, string $code, string $lang): void
    {
        $param = $kind === 'c' ? 'c' : 'r';
        $code = $kind === 'c' ? Campaign::normalizeCode($code) : User::normalizeReferralCode($code);

        if (! $code) {
            $this->bot->sendMessage($from['id'], $this->t('welcome', $lang));

            return;
        }

        $url = rtrim(config('app.frontend_url'), '/').($lang === 'ru' ? '/ru' : '').'/kirish?'.$param.'='.$code;

        $this->bot->sendMessage($from['id'], $this->t('campaign_welcome', $lang), [
            'inline_keyboard' => [[['text' => $this->t('campaign_button', $lang), 'url' => $url]]],
        ]);
    }

    private function handleContact(array $message, array $from, string $lang): void
    {
        $contact = $message['contact'];

        // Faqat o'zining raqamini qabul qilamiz.
        if (($contact['user_id'] ?? null) !== $from['id']) {
            $this->bot->sendMessage($from['id'], $this->t('own_contact_only', $lang));

            return;
        }

        $request = TelegramLoginRequest::usable()->where('telegram_id', $from['id'])->latest('id')->first();

        if (! $request) {
            $this->bot->sendMessage($from['id'], $this->t('expired', $lang), ['remove_keyboard' => true]);

            return;
        }

        $user = DB::transaction(function () use ($contact, $from, $lang, $request) {
            $phone = '+'.preg_replace('/\D/', '', $contact['phone_number']);

            $user = User::where('telegram_id', $from['id'])->first()
                ?? User::where('phone', $phone)->first()
                ?? new User(['locale' => $lang]);

            $isNew = ! $user->exists;

            $user->fill([
                'name' => $user->name ?? trim(($from['first_name'] ?? '').' '.($from['last_name'] ?? '')),
                'telegram_id' => $from['id'],
                'telegram_username' => Arr::get($from, 'username'),
                'phone' => $phone,
            ]);
            $user->phone_verified_at = now();
            if ($isNew) {
                $this->attribute($user, $request);
            }
            $user->save();

            Badge::where('slug', Badge::PHONE_VERIFIED)->first()?->grantTo($user);
            $request->approve($user);

            return $user;
        });

        $this->bot->sendMessage($from['id'], $this->t('approved', $user->locale), ['remove_keyboard' => true]);
    }

    /** Yangi foydalanuvchini kampaniyaga va taklif qilgan odamga bog'laydi (faqat birinchi kirishda). */
    private function attribute(User $user, TelegramLoginRequest $request): void
    {
        if ($request->campaign_code) {
            $campaign = Campaign::where('code', $request->campaign_code)->first();
            if ($campaign && $campaign->isRunning() && $campaign->hasRoom()) {
                $user->campaign_id = $campaign->id;
            }
        }

        if ($request->referral_code) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
            if ($referrer) {
                $user->referred_by_id = $referrer->id;
                // Kampaniya kodi bo'lmasa, taklif qilganning kampaniyasi meros bo'lmaydi (alohida hisob).
            }
        }
    }

    private function t(string $key, string $lang): string
    {
        $messages = [
            'welcome' => [
                'uz' => "Assalomu alaykum! 👋\n<b>enagalar.uz</b> — ishonchli enagalar platformasi.\nSaytga kirish uchun enagalar.uz da «Telegram orqali kirish» tugmasini bosing.",
                'ru' => "Здравствуйте! 👋\n<b>enagalar.uz</b> — платформа проверенных нянь.\nЧтобы войти, нажмите «Войти через Telegram» на сайте enagalar.uz.",
            ],
            'campaign_welcome' => [
                'uz' => "Assalomu alaykum! 👋\n<b>enagalar.uz</b> — ishonchli enagalar platformasi. Bonusingizni olish uchun saytga kiring.",
                'ru' => "Здравствуйте! 👋\n<b>enagalar.uz</b> — платформа проверенных нянь. Войдите на сайт, чтобы получить бонус.",
            ],
            'campaign_button' => ['uz' => 'Saytga kirish', 'ru' => 'Войти на сайт'],
            'share_contact' => [
                'uz' => 'Kirishni yakunlash uchun pastdagi tugma orqali telefon raqamingizni yuboring.',
                'ru' => 'Чтобы завершить вход, отправьте номер телефона кнопкой ниже.',
            ],
            'share_button' => ['uz' => '📱 Raqamni yuborish', 'ru' => '📱 Отправить номер'],
            'own_contact_only' => [
                'uz' => 'Iltimos, faqat o\'zingizning raqamingizni tugma orqali yuboring.',
                'ru' => 'Пожалуйста, отправьте свой номер с помощью кнопки.',
            ],
            'approved' => [
                'uz' => '✅ Kirish tasdiqlandi! Saytga qaytishingiz mumkin.',
                'ru' => '✅ Вход подтверждён! Можете вернуться на сайт.',
            ],
            'expired' => [
                'uz' => 'Havola eskirgan. Saytda «Telegram orqali kirish» tugmasini qaytadan bosing.',
                'ru' => 'Ссылка устарела. Нажмите «Войти через Telegram» на сайте ещё раз.',
            ],
        ];

        return $messages[$key][$lang] ?? $messages[$key]['uz'];
    }
}
