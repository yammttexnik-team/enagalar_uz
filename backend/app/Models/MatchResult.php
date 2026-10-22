<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Moslashtirish natijasi (jadval: matches). */
#[Fillable(['vacancy_id', 'nanny_profile_id', 'direction', 'score', 'reason_uz', 'reason_ru', 'source', 'computed_at'])]
class MatchResult extends Model
{
    public const FOR_VACANCY = 'for_vacancy';

    public const FOR_NANNY = 'for_nanny';

    public const SOURCE_AI = 'ai';

    public const SOURCE_RULES = 'rules';

    protected $table = 'matches';

    protected function casts(): array
    {
        return ['score' => 'integer', 'computed_at' => 'datetime'];
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function nannyProfile(): BelongsTo
    {
        return $this->belongsTo(NannyProfile::class);
    }
}
