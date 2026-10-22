<?php

namespace Tests\Support;

use App\Models\MatchResult;
use App\Services\Matching\MatchRanker;
use App\Services\Matching\MatchRun;
use App\Services\Matching\ReportsRun;
use Closure;

/** Testlar uchun soxta ranker: chaqiruvlarni yozib boradi. */
class FakeMatchRanker implements MatchRanker, ReportsRun
{
    /** @var array<int, array{subject: array, candidates: array, direction: string}> */
    public array $calls = [];

    public function __construct(private readonly ?Closure $respond = null) {}

    public function rank(array $subject, array $candidates, string $direction): array
    {
        $this->calls[] = compact('subject', 'candidates', 'direction');

        if ($this->respond) {
            return ($this->respond)($subject, $candidates, $direction);
        }

        return array_map(fn ($c) => [
            'id' => $c['id'], 'score' => 80, 'reason_uz' => 'Mos keladi', 'reason_ru' => 'Подходит',
        ], array_slice($candidates, 0, 5));
    }

    public function lastRun(): ?MatchRun
    {
        return new MatchRun(MatchResult::SOURCE_AI, 'fake-model', 100, 50, 'ok');
    }
}
