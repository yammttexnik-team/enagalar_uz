<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistrictResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => [
                'uz' => $this->name_uz,
                'uz_cyrl' => $this->name_uz_cyrl,
                'ru' => $this->name_ru,
            ],
        ];
    }
}
