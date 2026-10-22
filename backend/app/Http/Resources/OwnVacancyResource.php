<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Ota-onaning o'z e'loni: holat, ko'rishlar va arizalar soni bilan. */
class OwnVacancyResource extends VacancyResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'status' => $this->status->value,
            'region_id' => $this->region_id,
            'district_id' => $this->district_id,
            'views_count' => $this->views_count,
            'applications_count' => $this->whenCounted('applications'),
        ];
    }
}
