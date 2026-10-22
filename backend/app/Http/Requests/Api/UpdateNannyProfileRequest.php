<?php

namespace App\Http\Requests\Api;

use App\Enums\AgeGroup;
use App\Enums\Language;
use App\Enums\PriceUnit;
use App\Enums\WorkFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNannyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Tuman tanlangan viloyatga tegishli bo'lishi kerak (viloyat berilmasa, joriysi olinadi).
        $regionId = $this->input('region_id', $this->user()->nannyProfile?->region_id);

        return [
            'region_id' => ['sometimes', 'integer', 'exists:regions,id'],
            'district_id' => ['sometimes', 'nullable', 'integer', Rule::exists('districts', 'id')->where('region_id', $regionId)],
            'birth_year' => ['sometimes', 'nullable', 'integer', 'between:1950,'.(now()->year - 16)],
            'bio' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'experience_years' => ['sometimes', 'integer', 'between:0,60'],
            'price_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'price_unit' => ['sometimes', Rule::enum(PriceUnit::class)],
            'work_formats' => ['sometimes', 'array'],
            'work_formats.*' => [Rule::enum(WorkFormat::class)],
            'languages' => ['sometimes', 'array'],
            'languages.*' => [Rule::enum(Language::class)],
            'age_groups' => ['sometimes', 'array'],
            'age_groups.*' => [Rule::enum(AgeGroup::class)],
            'live_in' => ['sometimes', 'boolean'],
            'free_trial_day' => ['sometimes', 'boolean'],
            'is_available' => ['sometimes', 'boolean'],
            'skills' => ['sometimes', 'array'],
            'skills.*' => ['string', 'exists:skills,slug'],
        ];
    }
}
