<?php

namespace App\Http\Requests\Api;

use App\Enums\AgeGroup;
use App\Enums\Language;
use App\Enums\PriceUnit;
use App\Enums\WorkFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchNanniesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'region' => ['nullable', 'string', 'exists:regions,slug'],
            'district' => ['nullable', 'string', 'exists:districts,slug'],
            'work_format' => ['nullable', Rule::enum(WorkFormat::class)],
            'language' => ['nullable', Rule::enum(Language::class)],
            'age_group' => ['nullable', Rule::enum(AgeGroup::class)],
            'skill' => ['nullable', 'string', 'exists:skills,slug'],
            'price_max' => ['nullable', 'integer', 'min:0'],
            'price_unit' => ['nullable', Rule::enum(PriceUnit::class)],
            'experience_min' => ['nullable', 'integer', 'min:0', 'max:60'],
            'live_in' => ['nullable', 'boolean'],
            'available_only' => ['nullable', 'boolean'],
            'verified_only' => ['nullable', 'boolean'],
            'badge' => ['nullable', 'string', 'exists:badges,slug'],
            'sort' => ['nullable', Rule::in(['newest', 'rating', 'price_asc', 'experience'])],
        ];
    }
}
