<?php

namespace App\Http\Requests\Api;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Conversation $conversation */
        $conversation = $this->route('conversation');
        $type = $this->input('type');

        return [
            'type' => ['required', Rule::in(['text', 'image', 'voice'])],
            'body' => [Rule::requiredIf($type === 'text'), 'nullable', 'string', 'max:4000'],
            'file' => match ($type) {
                'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
                'voice' => ['required', 'file', 'mimetypes:audio/webm,video/webm,audio/ogg,application/ogg,video/ogg,audio/mp4,video/mp4,audio/x-m4a,audio/m4a,audio/mpeg,audio/mp3', 'max:5120'],
                default => ['prohibited'],
            },
            'duration' => ['nullable', 'integer', 'min:1', 'max:180'],
            'reply_to_id' => ['nullable', 'integer', Rule::exists('messages', 'id')->where('conversation_id', $conversation->id)],
        ];
    }

    public function messages(): array
    {
        return ['reply_to_id.exists' => 'Javob berilayotgan xabar shu suhbatda topilmadi.'];
    }
}
