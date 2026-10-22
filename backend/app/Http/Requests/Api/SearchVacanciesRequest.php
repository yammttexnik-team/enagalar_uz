<?php

namespace App\Http\Requests\Api;

use App\Enums\Language;
use App\Enums\PriceUnit;
use App\Enums\WorkFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchVacanciesRequest extends FormRequest
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
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_unit' => ['nullable', Rule::enum(PriceUnit::class)],
            'live_in' => ['nullable', 'boolean'],
            'is_urgent' => ['nullable', 'boolean'],
            'children_age' => ['nullable', 'integer', 'between:0,17'],
            'sort' => ['nullable', Rule::in(['newest', 'salary_desc'])],
        ];
    }
}
