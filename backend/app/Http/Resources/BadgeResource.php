<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BadgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => ['uz' => $this->name_uz, 'ru' => $this->name_ru],
            'description' => ['uz' => $this->description_uz, 'ru' => $this->description_ru],
            'icon' => $this->icon,
            'color' => $this->color,
        ];
    }
}
