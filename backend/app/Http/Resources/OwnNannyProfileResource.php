<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Enaganing o'z anketasi: holat va ko'rishlar soni bilan. */
class OwnNannyProfileResource extends NannyProfileResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'status' => $this->status->value,
            'region_id' => $this->region_id,
            'district_id' => $this->district_id,
            'birth_year' => $this->birth_year,
            'views_count' => $this->views_count,
        ];
    }
}
