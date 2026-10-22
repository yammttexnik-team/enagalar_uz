<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Savol: {id, type: single|multi|text|number|scale, text_uz, text_ru, options:[{value,uz,ru}],
 * required, min_length?, min_words?, max_select?, min?, max?, must_be? (diqqat savoli)}
 */
#[Fillable(['slug', 'audience', 'title_uz', 'title_ru', 'intro_uz', 'intro_ru', 'questions', 'is_active', 'bonus_amount'])]
class Survey extends Model
{
    public const TYPES = ['single', 'multi', 'text', 'number', 'scale'];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'is_active' => 'boolean',
            'bonus_amount' => 'integer',
        ];
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function title(string $lang): string
    {
        return $lang === 'ru' ? $this->title_ru : $this->title_uz;
    }
}
