<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IdentityVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'passport_series' => is_string($this->passport_series) ? mb_strtoupper(trim($this->passport_series)) : $this->passport_series,
            'passport_number' => is_string($this->passport_number) ? trim($this->passport_number) : $this->passport_number,
            'pinfl' => is_string($this->pinfl) ? trim($this->pinfl) : $this->pinfl,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:5', 'max:150'],
            'passport_series' => ['required', 'string', 'regex:/^[A-Z]{2}$/'],
            'passport_number' => ['required', 'string', 'regex:/^\d{7}$/'],
            'pinfl' => ['required', 'string', 'regex:/^\d{14}$/'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now()->subYears(18)->toDateString(), 'after_or_equal:'.now()->subYears(80)->toDateString()],
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'passport_series.regex' => 'Pasport seriyasi 2 ta lotin harfidan iborat bo\'lishi kerak (masalan, AD).',
            'passport_number.regex' => 'Pasport raqami 7 ta raqamdan iborat bo\'lishi kerak.',
            'pinfl.regex' => 'JShShIR 14 ta raqamdan iborat bo\'lishi kerak.',
            'birth_date.date_format' => 'Tug\'ilgan sana YYYY-MM-DD ko\'rinishida bo\'lishi kerak.',
            'birth_date.before_or_equal' => 'Yoshingiz kamida 18 da bo\'lishi kerak.',
            'birth_date.after_or_equal' => 'Tug\'ilgan sana noto\'g\'ri: yosh 80 dan oshmasligi kerak.',
            'photo.mimes' => 'Rasm jpg, png yoki webp formatida bo\'lishi kerak.',
            'photo.max' => 'Rasm hajmi 8 MB dan oshmasligi kerak.',
            'consent.accepted' => 'Shaxsiy ma\'lumotlarni qayta ishlashga rozilik berishingiz shart.',
        ];
    }
}
