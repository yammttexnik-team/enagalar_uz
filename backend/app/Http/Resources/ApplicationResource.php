<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'message' => $this->message,
            'created_at' => $this->created_at,
            'vacancy' => new VacancyResource($this->whenLoaded('vacancy')),
            'nanny' => new NannyProfileResource($this->whenLoaded('nannyProfile')),
        ];
    }
}
