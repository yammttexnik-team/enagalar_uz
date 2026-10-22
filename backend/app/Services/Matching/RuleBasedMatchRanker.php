<?php

namespace App\Services\Matching;

use App\Models\MatchResult;

/** Aniq qoidalarga asoslangan saralash (AI bo'lmaganda yoki limit tugaganda). */
class RuleBasedMatchRanker implements MatchRanker, ReportsRun
{
    /** @var array<string, array{0: string, 1: string}> mezon => [uz, ru] */
    private const REASONS = [
        'district' => ['Bir tumanda joylashgan', 'Находятся в одном районе'],
        'format' => ['Ish formati mos', 'Формат работы совпадает'],
        'language' => ['Muloqot tili mos', 'Совпадает язык общения'],
        'price' => ['Narx byudjetga mos', 'Цена укладывается в бюджет'],
        'age' => ['Bola yoshi bilan ishlash tajribasi bor', 'Есть опыт работы с детьми этого возраста'],
        'experience' => ['Tajribasi yetarli', 'Достаточный опыт работы'],
        'verified' => ['Tasdiqlangan profil', 'Подтверждённый профиль'],
        'rating' => ['Yuqori reyting', 'Высокий рейтинг'],
        'available' => ['Hozir ish qidirmoqda', 'Сейчас свободна для работы'],
    ];

    private ?MatchRun $run = null;

    public function rank(array $subject, array $candidates, string $direction): array
    {
        $scored = [];
        foreach ($candidates as $candidate) {
            [$nanny, $vacancy] = $direction === MatchResult::FOR_VACANCY ? [$candidate, $subject] : [$subject, $candidate];
            $result = self::evaluate($nanny, $vacancy);
            $scored[] = ['id' => (int) $candidate['id'], 'score' => $result['score'], 'criteria' => $result['criteria']];
        }

        usort($scored, fn ($a, $b) => [$b['score'], $a['id']] <=> [$a['score'], $b['id']]);

        $this->run = new MatchRun(MatchResult::SOURCE_RULES);

        return array_map(fn ($s) => [
            'id' => $s['id'],
            'score' => $s['score'],
            'reason_uz' => self::reason($s['criteria'], 0),
            'reason_ru' => self::reason($s['criteria'], 1),
        ], array_slice($scored, 0, (int) config('matching.top', 5)));
    }

    public function lastRun(): ?MatchRun
    {
        return $this->run;
    }

    /**
     * Enaga va e'lon juftligi bahosi (0-100) va mos kelgan mezonlar.
     *
     * @param  array<string, mixed>  $nanny
     * @param  array<string, mixed>  $vacancy
     * @return array{score: int, criteria: array<int, string>}
     */
    public static function evaluate(array $nanny, array $vacancy): array
    {
        $score = 0;
        $criteria = [];

        // Tuman (15)
        if ($nanny['district'] && $nanny['district'] === ($vacancy['district'] ?? null)) {
            $score += 15;
            $criteria[] = 'district';
        }

        // Ish formati (15)
        $formatOk = in_array($vacancy['work_format'] ?? null, $nanny['work_formats'] ?? [], true)
            || (($vacancy['live_in'] ?? false) && ($nanny['live_in'] ?? false));
        if ($formatOk) {
            $score += 15;
            $criteria[] = 'format';
        }

        // Til (10)
        $vLangs = $vacancy['languages'] ?? [];
        if ($vLangs === []) {
            $score += 5;
        } elseif (array_intersect($vLangs, $nanny['languages'] ?? []) !== []) {
            $score += 10;
            $criteria[] = 'language';
        }

        // Narx (20)
        $score += self::priceScore($nanny, $vacancy, $criteria);

        // Bola yoshi (15)
        $score += self::ageScore($nanny, $vacancy, $criteria);

        // Tajriba (10)
        $exp = (int) ($nanny['experience_years'] ?? 0);
        $score += min($exp, 5) * 2;
        if ($exp >= 2) {
            $criteria[] = 'experience';
        }

        // Tasdiqlangan (5)
        if (MatchPayload::isVerified($nanny)) {
            $score += 5;
            $criteria[] = 'verified';
        }

        // Reyting (5)
        if (($nanny['reviews_count'] ?? 0) > 0 && ($nanny['rating'] ?? 0) > 0) {
            $score += (int) round(min((float) $nanny['rating'], 5.0));
            if ($nanny['rating'] >= 4.5) {
                $criteria[] = 'rating';
            }
        }

        // Band emas (5)
        if ($nanny['is_available'] ?? true) {
            $score += 5;
            $criteria[] = 'available';
        }

        return ['score' => max(0, min(100, $score)), 'criteria' => $criteria];
    }

    private static function priceScore(array $nanny, array $vacancy, array &$criteria): int
    {
        $price = $nanny['price']['amount'] ?? null;
        $max = $vacancy['salary']['max'] ?? null;

        if ($price === null || $max === null || ($nanny['price']['unit'] ?? null) !== ($vacancy['salary']['unit'] ?? null)) {
            return 10; // taqqoslab bo'lmaydi — betaraf
        }

        if ($price <= $max) {
            $criteria[] = 'price';

            return 20;
        }

        return $price <= $max * (float) config('matching.price_tolerance', 1.2) ? 10 : 0;
    }

    private static function ageScore(array $nanny, array $vacancy, array &$criteria): int
    {
        $ages = $vacancy['children_ages'] ?? [];
        $groups = $nanny['age_groups'] ?? [];

        if ($ages === []) {
            return 7;
        }
        if ($groups === []) {
            return 5;
        }

        $needed = array_unique(array_map(self::groupFor(...), $ages));
        $covered = count(array_intersect($needed, $groups));

        if ($covered === count($needed)) {
            $criteria[] = 'age';

            return 15;
        }

        return $covered > 0 ? 8 : 0;
    }

    public static function groupFor(int $age): string
    {
        return match (true) {
            $age < 1 => 'infant',
            $age < 3 => 'toddler',
            $age < 6 => 'preschool',
            default => 'school',
        };
    }

    /** Eng muhim 3 ta mezondan 140 belgigacha sabab. */
    private static function reason(array $criteria, int $lang): string
    {
        $order = ['district', 'price', 'format', 'age', 'language', 'experience', 'verified', 'rating'];
        $picked = array_slice(array_values(array_intersect($order, $criteria)), 0, 3);

        $fallback = [
            'Bir viloyatda, ba\'zi mezonlar qisman mos',
            'В одном регионе, часть критериев совпадает',
        ][$lang];

        if ($picked === []) {
            return $fallback;
        }

        $text = implode(', ', array_map(fn ($c) => self::REASONS[$c][$lang], $picked));

        return mb_strimwidth(mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1), 0, 140, '', 'UTF-8');
    }
}
