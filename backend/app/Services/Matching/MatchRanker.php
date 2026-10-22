<?php

namespace App\Services\Matching;

/**
 * Mavzu (e'lon yoki enaga) uchun nomzodlarni saralaydi.
 *
 * Kirish ma'lumotlari MatchPayload orqali anonimlashtirilgan massivlar.
 */
interface MatchRanker
{
    /**
     * @param  array<string, mixed>  $subject  anonim mavzu
     * @param  array<int, array<string, mixed>>  $candidates  anonim nomzodlar (har birida 'id')
     * @param  string  $direction  MatchResult::FOR_VACANCY | MatchResult::FOR_NANNY
     * @return array<int, array{id: int, score: int, reason_uz: string, reason_ru: string}> eng yaxshi 5 tasi
     */
    public function rank(array $subject, array $candidates, string $direction): array;
}
