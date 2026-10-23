<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Ota-onaning o'z e'loni: holat, ko'rishlar va arizalar soni bilan. */
class OwnVacancyResource extends VacancyResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            // Tahrirlash formasi uchun: ota-onaning o'z belgisi (sotib olingani — urgent_until).
            'is_urgent' => $this->is_urgent,
            'top_until' => $this->top_until,
            'urgent_until' => $this->urgent_until,
            'status' => $this->status->value,
            'region_id' => $this->region_id,
            'district_id' => $this->district_id,
            'views_count' => $this->views_count,
            'applications_count' => $this->whenCounted('applications'),
        ]);
    }
}
