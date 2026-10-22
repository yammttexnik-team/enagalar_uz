<?php

namespace App\Http\Resources;

use App\Models\MatchResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Moslik: ball, sabab va qarshi tomonning ochiq ko'rinishi (maxfiylik qoidalari bilan). */
class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'score' => $this->score,
            'reason' => ['uz' => $this->reason_uz, 'ru' => $this->reason_ru],
            'source' => $this->source,
            'computed_at' => $this->computed_at,
            $this->mergeWhen($this->direction === MatchResult::FOR_VACANCY, fn () => [
                'nanny' => new NannyProfileResource($this->nannyProfile),
            ]),
            $this->mergeWhen($this->direction === MatchResult::FOR_NANNY, fn () => [
                'vacancy' => new VacancyResource($this->vacancy),
            ]),
        ];
    }
}
