<?php

namespace App\Http\Requests\Api;

use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // "sent" holatiga qaytarib bo'lmaydi.
        return [
            'status' => ['required', Rule::enum(ApplicationStatus::class)->except(ApplicationStatus::Sent)],
        ];
    }
}
