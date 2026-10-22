<?php

namespace App\Http\Requests\Api;

use App\Models\Badge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'badge' => ['required', 'string', Rule::exists('badges', 'slug')
                ->where('is_active', true)
                ->where('grant_type', 'manual')
                ->whereNot('slug', Badge::VERIFIED)],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'issued_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'consent' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->hasAny(['badge', 'issued_at'])) {
                return;
            }

            $days = Badge::where('slug', $this->input('badge'))->value('validity_days');

            if ($days && $this->date('issued_at')->lt(now()->subDays($days)->startOfDay())) {
                $validator->errors()->add('issued_at', "Hujjat muddati o'tgan: u oxirgi {$days} kun ichida berilgan bo'lishi kerak.");
            }
        }];
    }

    public function messages(): array
    {
        return [
            'badge.exists' => 'Bu nishon uchun hujjat yuborib bo\'lmaydi.',
            'file.mimes' => 'Fayl jpg, png, webp yoki pdf formatida bo\'lishi kerak.',
            'file.max' => 'Fayl hajmi 10 MB dan oshmasligi kerak.',
            'issued_at.date_format' => 'Berilgan sana YYYY-MM-DD ko\'rinishida bo\'lishi kerak.',
            'issued_at.before_or_equal' => 'Berilgan sana kelajakda bo\'lishi mumkin emas.',
            'consent.accepted' => 'Ma\'lumotlarni qayta ishlashga rozilik berishingiz shart.',
        ];
    }
}
