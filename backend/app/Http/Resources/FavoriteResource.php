<?php

namespace App\Http\Resources;

use App\Models\NannyProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isNanny = $this->favoritable instanceof NannyProfile;

        return [
            'type' => $isNanny ? 'nanny' : 'vacancy',
            'created_at' => $this->created_at,
            'item' => $isNanny ? new NannyProfileResource($this->favoritable) : new VacancyResource($this->favoritable),
        ];
    }
}
