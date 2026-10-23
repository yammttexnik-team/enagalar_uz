<?php

namespace App\Services\Telegram;

use App\Enums\PriceUnit;
use App\Models\ChannelPost;
use App\Models\ImportedListing;
use App\Models\NannyProfile;
use App\Models\Vacancy;
use Illuminate\Support\Carbon;

/** Kunlik "Bugungi e'lonlar" digestini yig'adi va kanalga chiqaradi. Shablon asosida, AI chaqirilmaydi. */
class ChannelDigest
{
    public const MIN_ITEMS = 3;

    public const MAX_ITEMS = 8;

    private const VACANCY_EMOJI = '👨‍👩‍👧';

    private const NANNY_EMOJI = '👩‍🍼';

    public function __construct(private TelegramBot $bot) {}

    /** Digest yaratadi: yetarli e'lon bo'lmasa `skipped`, aks holda `draft`. */
    public function build(): ChannelPost
    {
        $items = $this->collect($this->since());

        if (count($items) < self::MIN_ITEMS) {
            return ChannelPost::create(['text' => '', 'status' => ChannelPost::SKIPPED, 'items_count' => count($items)]);
        }

        return ChannelPost::create([
            'text' => $this->render($items),
            'status' => ChannelPost::DRAFT,
            'items_count' => count($items),
        ]);
    }

    /** Postni kanalga yuboradi va holatini yangilaydi. */
    public function publish(ChannelPost $post): bool
    {
        $response = null;
        try {
            $response = $this->bot->sendMessage((string) config('services.telegram.channel_id'), $post->text, null, true);
            $ok = $response->successful() && $response->json('ok');
            $error = $ok ? null : (string) ($response->json('description') ?? 'HTTP '.$response->status());
        } catch (\Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
        }

        $post->update($ok ? [
            'status' => ChannelPost::SENT,
            'sent_at' => now(),
            'telegram_message_id' => $response->json('result.message_id'),
            'error' => null,
        ] : [
            'status' => ChannelPost::FAILED,
            'error' => mb_substr((string) $error, 0, 500),
        ]);

        return (bool) $ok;
    }

    /** Adminga qoralama ko'rinishini yuboradi. */
    public function notifyAdmin(ChannelPost $post): void
    {
        $chatId = config('services.telegram.admin_chat_id');
        if (! $chatId) {
            return;
        }

        $text = $post->text."\n\nKanal uchun digest tayyor — admin paneldan chiqaring: ".rtrim((string) config('app.url'), '/').'/admin/channel-posts';

        try {
            $this->bot->sendMessage((string) $chatId, $text, null, true);
        } catch (\Throwable) {
            // Xabar yetib bormasa ham qoralama saqlanib qoladi
        }
    }

    private function since(): Carbon
    {
        $last = ChannelPost::where('kind', 'digest')->where('status', ChannelPost::SENT)->latest('sent_at')->value('sent_at');
        $day = now()->subDay();

        return $last && Carbon::parse($last)->gt($day) ? Carbon::parse($last) : $day;
    }

    /** @return list<array{emoji: string, title: string, place: string, price: ?string, url: string}> */
    private function collect(Carbon $since): array
    {
        $base = rtrim((string) config('app.frontend_url'), '/');
        $items = [];

        $vacancies = Vacancy::active()->with(['region', 'district'])
            ->where('published_at', '>=', $since)->latest('published_at')->limit(self::MAX_ITEMS)->get();
        foreach ($vacancies as $v) {
            $items[] = [
                'emoji' => self::VACANCY_EMOJI,
                'title' => $v->title ?: 'Enaga kerak',
                'place' => $this->place($v),
                'price' => $this->price($v->salary_min, $v->salary_unit),
                'url' => "$base/vakansiyalar/{$v->id}",
            ];
        }

        $nannies = NannyProfile::active()->with(['region', 'district'])
            ->where('published_at', '>=', $since)->latest('published_at')->limit(self::MAX_ITEMS)->get();
        foreach ($nannies as $n) {
            $items[] = [
                'emoji' => self::NANNY_EMOJI,
                'title' => $n->experience_years ? "Enaga, {$n->experience_years} yil tajriba" : 'Enaga',
                'place' => $this->place($n),
                'price' => $this->price($n->price_amount, $n->price_unit),
                'url' => "$base/enagalar/{$n->id}",
            ];
        }

        $imported = ImportedListing::visible()->with(['region', 'district'])
            ->where('published_at', '>=', $since)->latest('published_at')->limit(self::MAX_ITEMS)->get();
        foreach ($imported as $r) {
            $items[] = [
                'emoji' => $r->type === ImportedListing::TYPE_NANNY ? self::NANNY_EMOJI : self::VACANCY_EMOJI,
                'title' => $r->title ?: "E'lon",
                'place' => $this->place($r),
                'price' => $this->price($r->price_min, $r->price_unit),
                'url' => "$base/elon/{$r->id}",
            ];
        }

        // Vakansiyalar (ota-ona qidiryapti) birinchi, keyin enagalar (usort barqaror)
        usort($items, fn ($a, $b) => (int) ($a['emoji'] !== self::VACANCY_EMOJI) <=> (int) ($b['emoji'] !== self::VACANCY_EMOJI));

        return array_slice($items, 0, self::MAX_ITEMS);
    }

    private function place(object $model): string
    {
        return (string) ($model->district?->name_uz ?: $model->region?->name_uz ?: '');
    }

    private function price(mixed $amount, mixed $unit): ?string
    {
        if (! $amount) {
            return null;
        }
        $label = match ($unit) {
            PriceUnit::Hour => 'soat',
            PriceUnit::Day => 'kun',
            PriceUnit::Month => 'oy',
            default => null,
        };

        return number_format((int) $amount, 0, '', ' ')." so'm".($label ? "/$label" : '');
    }

    /** @param list<array{emoji: string, title: string, place: string, price: ?string, url: string}> $items */
    private function render(array $items): string
    {
        $base = rtrim((string) config('app.frontend_url'), '/');
        $lines = fn (string $cta) => implode("\n", array_map(function ($i) use ($cta) {
            $meta = implode(', ', array_filter([$i['place'], $i['price']]));

            return $i['emoji'].' '.e($i['title']).($meta ? ' — '.e($meta) : '').' <a href="'.e($i['url']).'">'.$cta.'</a>';
        }, $items));

        $bot = 'https://t.me/'.config('services.telegram.bot_username', 'enagalaruz_bot').'?start=c_DIGEST';

        return "<b>Bugungi e'lonlar</b>\n\n"
            .$lines('Batafsil')."\n\n"
            ."———\n\n"
            ."<b>Объявления дня</b>\n\n"
            .$lines('Подробнее')."\n\n"
            ."<a href=\"$base/vakansiyalar\">Barcha e'lonlar</a> · <a href=\"$base/enagalar\">Enagalar</a> · <a href=\"$bot\">E'lon joylash</a>";
    }
}
