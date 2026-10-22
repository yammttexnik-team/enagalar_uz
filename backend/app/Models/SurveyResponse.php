<?php

namespace App\Models;

use App\Enums\SurveyResponseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['survey_id', 'user_id', 'answers', 'status', 'bonus_awarded_at', 'quality_note', 'duration_seconds'])]
class SurveyResponse extends Model
{
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'status' => SurveyResponseStatus::class,
            'bonus_awarded_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
