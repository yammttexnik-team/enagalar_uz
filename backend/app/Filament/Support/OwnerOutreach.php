<?php

namespace App\Filament\Support;

use App\Enums\ImportedListingStatus;
use App\Models\ImportedListing;
use App\Models\ImportSource;
use App\Services\Imports\ListingImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;

/**
 * Import e'lon egasiga qo'lda shaxsiy xabar: kontakt, tayyor matn va "yozildi" belgisi.
 * Xabarni moderator o'zi alohida Telegram akkauntdan yuboradi (MARKETING_REJA.md, 6-bo'lim).
 */
class OwnerOutreach
{
    public const CAMPAIGN = 'CLAIM';

    /** Kuniga tavsiya etilgan chegara (spam deb belgilanmaslik uchun). */
    public const DAILY_LIMIT = 15;

    /** @return list<array{label: string, url: string}> */
    public static function contacts(ImportedListing $listing): array
    {
        $raw = (string) $listing->raw_text;
        $ignored = [...ImportSource::pluck('channel')->map(fn ($c) => mb_strtolower($c))->all(), mb_strtolower($listing->source_channel)];

        $result = [];
        foreach (ListingImporter::extractUsernames($raw) as $name) {
            $lower = mb_strtolower($name);
            if (! in_array($lower, $ignored, true) && ! str_ends_with($lower, 'bot')) {
                $result[] = ['label' => '@'.$name, 'url' => 'https://t.me/'.$name];
            }
        }
        foreach (ListingImporter::extractPhones($raw) as $digits) {
            $result[] = ['label' => '+998'.$digits, 'url' => 'https://t.me/+998'.$digits];
        }

        return $result;
    }

    /** Ruscha yozilgan e'longa ruscha xabar (o'zbek kirill harflari bo'lsa — o'zbekcha). */
    public static function language(ImportedListing $listing): string
    {
        $raw = mb_strtolower((string) $listing->raw_text);

        return preg_match('~[а-яё]~u', $raw) && ! preg_match('~[ўқғҳ]~u', $raw) && ! preg_match('~enaga|kerak|bola~u', $raw) ? 'ru' : 'uz';
    }

    public static function message(ImportedListing $listing, ?string $lang = null): string
    {
        $lang ??= self::language($listing);
        $base = rtrim((string) config('app.frontend_url'), '/');
        $url = $base.($lang === 'ru' ? '/ru' : '').'/elon/'.$listing->id.'?c='.self::CAMPAIGN;
        $channel = '@'.(preg_match('~t\.me/(?:s/)?([A-Za-z0-9_]+)/\d+~', (string) $listing->source_url, $m) ? $m[1] : $listing->source_channel);
        $days = (int) config('imports.claim_top_days');
        $bonus = number_format((int) config('bonus.listing'), 0, '', ' ');

        if ($lang === 'ru') {
            return "Здравствуйте! Увидели ваше объявление в $channel — оно также показано на сайте enagalar.uz: $url\n\n"
                ."Войдите через Telegram и нажмите «Это моё объявление» — оно перейдёт к вам: $days дней в ТОПе бесплатно и $bonus сум бонусами после проверки (бонусы для услуг сайта, не обналичиваются).\n\n"
                .'Если не хотите, чтобы объявление было на сайте, удалить можно на той же странице. Извините за беспокойство.';
        }

        return "Assalomu alaykum! E'loningizni $channel kanalida ko'rdik — u enagalar.uz saytida ham ko'rinyapti: $url\n\n"
            ."Telegram orqali kirib «Bu mening e'lonim» tugmasini bossangiz, e'lon sizning nomingizga o'tadi: $days kun TOP bepul va tekshiruvdan o'tgach $bonus so'm bonus (bonus sayt xizmatlari uchun, naqdga aylanmaydi).\n\n"
            ."Saytda turishini xohlamasangiz, shu sahifaning o'zida o'chirasiz. Bezovta qilgan bo'lsak, uzr.";
    }

    /** Shu kontaktga (boshqa e'lon orqali) avval yozilganmi: bir odamga bir marta. */
    public static function alreadyContacted(ImportedListing $listing): bool
    {
        $hashes = $listing->contact_hashes ?? [];
        if ($hashes === []) {
            return false;
        }

        return ImportedListing::whereNotNull('outreach_at')
            ->where(function ($q) use ($hashes) {
                foreach ($hashes as $hash) {
                    $q->orWhereJsonContains('contact_hashes', $hash);
                }
            })
            ->exists();
    }

    public static function sentToday(): int
    {
        return ImportedListing::where('outreach_at', '>=', now()->startOfDay())->count();
    }

    public static function action(): Action
    {
        return Action::make('outreach')
            ->label('Egasiga yozish')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->color('info')
            ->visible(fn (ImportedListing $record) => $record->status === ImportedListingStatus::Active
                && $record->outreach_at === null && $record->claimed_by_user_id === null && $record->removal_requested_at === null
                && ! self::alreadyContacted($record))
            ->modalHeading('E\'lon egasiga shaxsiy xabar')
            ->modalDescription(fn () => 'Bugun yozilgan: '.self::sentToday().' / '.self::DAILY_LIMIT.". Xabarni alohida Telegram akkauntdan, 10:00–17:30 oralig'ida yuboring. Yuborgach \"Yozildi\" ni bosing.")
            ->schema(fn (ImportedListing $record) => [
                Text::make(fn () => self::contacts($record) === []
                    ? 'Kontakt topilmadi — asl postni oching.'
                    : 'Kontakt: '.collect(self::contacts($record))->pluck('label')->implode(', ')),
                Textarea::make('text')->label('Xabar matni (nusxa oling)')->rows(9)
                    ->default(self::message($record))->readOnly(),
            ])
            ->extraModalFooterActions(fn (ImportedListing $record) => collect(self::contacts($record))->take(2)
                ->map(fn ($c, $i) => Action::make('open'.$i)->label($c['label'].' — ochish')->color('gray')
                    ->url($c['url'], shouldOpenInNewTab: true))
                ->all())
            ->modalSubmitActionLabel('Yozildi')
            ->action(function (ImportedListing $record) {
                $record->update(['outreach_at' => now(), 'outreach_by' => auth()->id()]);
                Notification::make()->title('Belgilandi')->success()->send();
            });
    }
}
