<?php

namespace App\Services\Matching;

use App\Models\MatchResult;

/** Bitta rank() chaqiruvining natijasi: manba va AI sarfi. */
final readonly class MatchRun
{
    public function __construct(
        public string $source = MatchResult::SOURCE_RULES,
        public ?string $model = null,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public string $status = 'ok',
    ) {}

    /** AI ga murojaat qilingan (muvaffaqiyatli yoki yo'q) — ai_requests ga yoziladi. */
    public function calledAi(): bool
    {
        return $this->model !== null;
    }
}
