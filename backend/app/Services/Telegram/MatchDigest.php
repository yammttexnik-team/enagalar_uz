<?php

namespace App\Services\Telegram;

use App\Enums\PriceUnit;
use App\Models\ImportedListing;
use App\Models\NannyProfile;
use App\Models\Region;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Carbon;

/** Foydalanuvchining hududidagi yangi e'lonlar (enaga uchun vakansiyalar, ota-ona uchun enagalar) va xabar matni. */
class MatchDigest
{
    public const MAX_LINES = 5;

    public function __construct(private UserNotifier $notifier) {}

    /**
     * @return array{count: int, lines: list<string>, region: string, path: string}|null
     */
    public function build(User $user, Carbon $since): ?array
    {
        $lang = $user->locale === 'ru' ? 'ru' : 'uz';
        $isNanny = $user->hasRole('nanny');
        $isParent = $user->hasRole('parent');

        if ($isNanny && ($profile = $user->nannyProfile) && $profile->region_id) {
            $regionIds = [$profile->region_id];
            $entries = $this->vacancies($regionIds, $since, $lang);
            $path = '/vakansiyalar';
        } elseif ($isParent) {
            $regionIds = Vacancy::withoutGlobalScopes()
                ->whereHas('parentProfile', fn ($q) => $q->where('user_id', $user->id))
                ->whereNotNull('region_id')->latest('id')->pluck('region_id')->unique()->values()->all();
            if (! $regionIds) {
                return null;
            }
            $entries = $this->nannies($regionIds, $since, $lang, $user->id);
            $path = '/enagalar';
        } else {
            return null;
        }

        if (! $entries) {
            return null;
        }

        usort($entries, fn ($a, $b) => $b['at'] <=> $a['at']);
        $slug = (string) Region::where('id', $regionIds[0])->value('slug');

        return [
            'count' => count($entries),
            'lines' => array_map(fn ($e) => $e['line'], array_slice($entries, 0, self::MAX_LINES)),
            'region' => $slug,
            'path' => $path,
        ];
    }

    public function send(User $user, array $digest): bool
    {
        $lang = $user->locale === 'ru' ? 'ru' : 'uz';
        $header = $lang === 'ru'
            ? "В вашем регионе новых объявлений: {$digest['count']}"
            : "Hududingizda {$digest['count']} ta yangi eʼlon";
        $footer = $lang === 'ru' ? 'Отключить уведомления: /stop' : 'Xabarlarni oʻchirish: /stop';
        $button = $lang === 'ru' ? 'Смотреть объявления' : 'Eʼlonlarni koʻrish';

        $text = "<b>$header</b>\n\n".implode("\n", $digest['lines'])."\n\n$footer";
        $url = UserNotifier::url($lang, $digest['path']).'?region='.$digest['region'];

        return $this->notifier->send($user, $text, ['inline_keyboard' => [[['text' => $button, 'url' => $url]]]]);
    }

    /** @return list<array{at: Carbon, line: string}> */
    private function vacancies(array $regionIds, Carbon $since, string $lang): array
    {
        $out = [];

        Vacancy::active()->with(['region', 'district'])->whereIn('region_id', $regionIds)
            ->where('published_at', '>', $since)->latest('published_at')->limit(self::MAX_LINES)->get()
            ->each(function (Vacancy $v) use (&$out, $lang) {
                $out[] = ['at' => $v->published_at, 'line' => $this->line($v->title ?: ($lang === 'ru' ? 'Нужна няня' : 'Enaga kerak'), $v, $v->salary_min, $v->salary_unit, $lang)];
            });

        ImportedListing::visible()->with(['region', 'district'])->where('type', ImportedListing::TYPE_VACANCY)
            ->whereIn('region_id', $regionIds)->where('source_posted_at', '>', $since)
            ->latest('source_posted_at')->limit(self::MAX_LINES)->get()
            ->each(function (ImportedListing $l) use (&$out, $lang) {
                $out[] = ['at' => $l->source_posted_at, 'line' => $this->importedLine($l, $lang)];
            });

        return $out;
    }

    /** @return list<array{at: Carbon, line: string}> */
    private function nannies(array $regionIds, Carbon $since, string $lang, int $exceptUserId): array
    {
        $out = [];

        NannyProfile::active()->with(['region', 'district'])->whereIn('region_id', $regionIds)
            ->where('user_id', '!=', $exceptUserId)
            ->where('published_at', '>', $since)->latest('published_at')->limit(self::MAX_LINES)->get()
            ->each(function (NannyProfile $n) use (&$out, $lang) {
                $title = $lang === 'ru'
                    ? ($n->experience_years ? "Няня, опыт {$n->experience_years} лет" : 'Няня')
                    : ($n->experience_years ? "Enaga, {$n->experience_years} yil tajriba" : 'Enaga');
                $out[] = ['at' => $n->published_at, 'line' => $this->line($title, $n, $n->price_amount, $n->price_unit, $lang)];
            });

        ImportedListing::visible()->with(['region', 'district'])->where('type', ImportedListing::TYPE_NANNY)
            ->whereIn('region_id', $regionIds)->where('source_posted_at', '>', $since)
            ->latest('source_posted_at')->limit(self::MAX_LINES)->get()
            ->each(function (ImportedListing $l) use (&$out, $lang) {
                $out[] = ['at' => $l->source_posted_at, 'line' => $this->importedLine($l, $lang)];
            });

        return $out;
    }

    private function importedLine(ImportedListing $l, string $lang): string
    {
        $title = $lang === 'ru' ? ($l->title_ru ?: $l->title) : ($l->title_uz ?: $l->title);

        return $this->line($title ?: ($lang === 'ru' ? 'Объявление' : 'Eʼlon'), $l, $l->price_min, $l->price_unit, $lang);
    }

    private function line(string $title, object $model, mixed $amount, mixed $unit, string $lang): string
    {
        $col = $lang === 'ru' ? 'name_ru' : 'name_uz';
        $place = (string) ($model->district?->$col ?: $model->region?->$col ?: '');
        $meta = implode(', ', array_filter([$place, $this->price($amount, $unit, $lang)]));

        return '• '.e(mb_strimwidth($title, 0, 80, '…')).($meta ? ' — '.e($meta) : '');
    }

    private function price(mixed $amount, mixed $unit, string $lang): ?string
    {
        if (! $amount) {
            return null;
        }
        $ru = $lang === 'ru';
        $label = match ($unit) {
            PriceUnit::Hour => $ru ? 'час' : 'soat',
            PriceUnit::Day => $ru ? 'день' : 'kun',
            PriceUnit::Month => $ru ? 'мес' : 'oy',
            default => null,
        };

        return number_format((int) $amount, 0, '', ' ').($ru ? ' сум' : " so'm").($label ? "/$label" : '');
    }
}
