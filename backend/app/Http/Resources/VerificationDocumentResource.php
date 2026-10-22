<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Foydalanuvchining o'z hujjati: fayl yo'li hech qachon qaytarilmaydi. */
class VerificationDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'badge' => $this->badge ? [
                'slug' => $this->badge->slug,
                'name' => ['uz' => $this->badge->name_uz, 'ru' => $this->badge->name_ru],
            ] : null,
            'status' => $this->status->value,
            'rejection_reason' => $this->rejection_reason,
            'issued_at' => $this->issued_at?->toDateString(),
            'submitted_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
