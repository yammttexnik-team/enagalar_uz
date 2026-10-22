<?php

namespace App\Http\Requests\Api;

use App\Enums\Language;
use App\Enums\PriceUnit;
use App\Enums\WorkFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** E'lon yaratish (POST) va tahrirlash (PUT) uchun. */
class VacancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'district_id' => ['nullable', 'integer', Rule::exists('districts', 'id')->where('region_id', $this->input('region_id'))],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'work_format' => ['required', Rule::enum(WorkFormat::class)],
            'schedule' => ['nullable', 'string', 'max:255'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'gte:salary_min'],
            'salary_unit' => ['nullable', Rule::enum(PriceUnit::class)],
            'children_count' => ['required', 'integer', 'between:1,10'],
            'children_ages' => ['nullable', 'array', 'max:10'],
            'children_ages.*' => ['integer', 'between:0,17'],
            'languages' => ['nullable', 'array'],
            'languages.*' => [Rule::enum(Language::class)],
            'live_in' => ['nullable', 'boolean'],
            'is_urgent' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
        ];
    }
}
