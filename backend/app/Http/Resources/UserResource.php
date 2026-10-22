<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Foydalanuvchining o'zi uchun (shaxsiy kabinet). */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'telegram_username' => $this->telegram_username,
            'locale' => $this->locale,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'badges' => BadgeResource::collection($this->whenLoaded('activeBadges')),
        ];
    }
}
