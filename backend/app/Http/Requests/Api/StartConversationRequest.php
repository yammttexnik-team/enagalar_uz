<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Uchtadan aynan bittasi kerak: nanny_profile_id | vacancy_id | application_id. */
    public function rules(): array
    {
        return [
            'nanny_profile_id' => ['nullable', 'integer', 'required_without_all:vacancy_id,application_id'],
            'vacancy_id' => ['nullable', 'integer', 'required_without_all:nanny_profile_id,application_id'],
            'application_id' => ['nullable', 'integer', 'required_without_all:nanny_profile_id,vacancy_id'],
        ];
    }
}
