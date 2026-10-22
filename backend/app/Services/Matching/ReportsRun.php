<?php

namespace App\Services\Matching;

/** Oxirgi rank() chaqiruvi haqida hisobot beradigan ranker (AI sarfini yozish uchun). */
interface ReportsRun
{
    public function lastRun(): ?MatchRun;
}
