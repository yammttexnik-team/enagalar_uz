<?php

namespace App\Services\Matching;

use App\Models\Badge;
use App\Models\NannyProfile;
use App\Models\Vacancy;

/**
 * AI ga yuboriladigan ANONIM ma'lumot. Ism, telefon, Telegram, pasport hech qachon kirmaydi;
 * erkin matnlardan telefon, @username, havola va email olib tashlanadi.
 */
class MatchPayload
{
    public const TEXT_LIMIT = 400;

    /** Bog'lamalar: skills, district, user.activeBadges yuklangan bo'lishi kerak. */
    public static function nanny(NannyProfile $p): array
    {
        return [
            'id' => $p->id,
            'district' => $p->district?->slug,
            'experience_years' => (int) $p->experience_years,
            'price' => ['amount' => $p->price_amount, 'unit' => $p->price_unit?->value],
            'work_formats' => array_values($p->work_formats ?? []),
            'languages' => array_values($p->languages ?? []),
            'age_groups' => array_values($p->age_groups ?? []),
            'skills' => $p->skills->pluck('slug')->values()->all(),
            'live_in' => (bool) $p->live_in,
            'is_available' => (bool) $p->is_available,
            'badges' => self::badges($p->user?->activeBadges),
            'rating' => (float) $p->rating_avg,
            'reviews_count' => (int) $p->reviews_count,
            'bio' => self::clean($p->bio, self::TEXT_LIMIT),
        ];
    }

    /** Bog'lamalar: district, parentProfile.user.activeBadges yuklangan bo'lishi kerak. */
    public static function vacancy(Vacancy $v): array
    {
        return [
            'id' => $v->id,
            'district' => $v->district?->slug,
            'title' => self::clean($v->title, 120),
            'description' => self::clean($v->description, self::TEXT_LIMIT),
            'schedule' => self::clean($v->schedule, 120),
            'work_format' => $v->work_format?->value,
            'salary' => ['min' => $v->salary_min, 'max' => $v->salary_max, 'unit' => $v->salary_unit?->value],
            'children_ages' => array_values($v->children_ages ?? []),
            'languages' => array_values($v->languages ?? []),
            'live_in' => (bool) $v->live_in,
            'is_urgent' => (bool) $v->is_urgent,
            'badges' => self::badges($v->parentProfile?->user?->activeBadges),
        ];
    }

    /** Erkin matndan aloqa ma'lumotlarini olib tashlab, qisqartiradi. */
    public static function clean(?string $text, int $limit): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $text = preg_replace('~(?:https?://|www\.)\S+~iu', ' ', $text);
        $text = preg_replace('~\b(?:t\.me|telegram\.me|instagram\.com|wa\.me)/\S*~iu', ' ', $text);
        $text = preg_replace('~[\w.+-]+@[\w-]+(?:\.[\w-]+)+~u', ' ', $text);
        $text = preg_replace('~@\w+~u', ' ', $text);
        // Raqamlar ketma-ketligi (bo'shliq, tire, qavs bilan) — 7 va undan ko'p raqam telefon deb hisoblanadi.
        $text = preg_replace_callback('~\+?\d[\d\s\-().]{5,}\d~u', function (array $m) {
            return strlen(preg_replace('/\D/', '', $m[0])) >= 7 ? ' ' : $m[0];
        }, $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return $text === '' ? null : mb_substr($text, 0, $limit);
    }

    /** @return array<int, string> */
    private static function badges($badges): array
    {
        return $badges ? $badges->pluck('slug')->values()->all() : [];
    }

    public static function isVerified(array $payload): bool
    {
        return in_array(Badge::VERIFIED, $payload['badges'] ?? [], true);
    }
}
