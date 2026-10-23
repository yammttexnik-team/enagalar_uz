<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** E'lon — ochiq ko'rinish. Ota-onaning faqat ismi va familiyasining bosh harfi chiqadi. */
class VacancyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->relationLoaded('parentProfile') ? $this->parentProfile?->user : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'region' => $this->whenLoaded('region', fn () => $this->region ? ['id' => $this->region->id, 'slug' => $this->region->slug, 'name' => ['uz' => $this->region->name_uz, 'ru' => $this->region->name_ru]] : null),
            'district' => $this->whenLoaded('district', fn () => $this->district ? ['id' => $this->district->id, 'slug' => $this->district->slug, 'name' => ['uz' => $this->district->name_uz, 'ru' => $this->district->name_ru]] : null),
            'work_format' => $this->work_format->value,
            'schedule' => $this->schedule,
            'salary' => ['min' => $this->salary_min, 'max' => $this->salary_max, 'unit' => $this->salary_unit?->value],
            'children_count' => $this->children_count,
            'children_ages' => $this->children_ages ?? [],
            'languages' => $this->languages ?? [],
            'live_in' => $this->live_in,
            'is_urgent' => $this->isUrgentNow(),
            'is_top' => $this->isTop(),
            'starts_at' => $this->starts_at?->toDateString(),
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
            'parent' => $user ? [
                'name' => $user->publicName(),
                'badges' => BadgeResource::collection($user->relationLoaded('activeBadges') ? $user->activeBadges : []),
            ] : null,
        ];
    }
}
