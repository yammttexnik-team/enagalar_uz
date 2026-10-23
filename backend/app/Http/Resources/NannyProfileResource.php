<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Enaga anketasi — ochiq ko'rinish. Telefon, Telegram va pasport ma'lumotlari hech qachon qaytarilmaydi.
 */
class NannyProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->publicName(),
            'age' => $this->birth_year ? now()->year - $this->birth_year : null,
            'region' => $this->whenLoaded('region', fn () => $this->region ? $this->namesOf($this->region) : null),
            'district' => $this->whenLoaded('district', fn () => $this->district ? $this->namesOf($this->district) : null),
            'bio' => $this->bio,
            'experience_years' => $this->experience_years,
            'price' => ['amount' => $this->price_amount, 'unit' => $this->price_unit?->value],
            'work_formats' => $this->work_formats ?? [],
            'languages' => $this->languages ?? [],
            'age_groups' => $this->age_groups ?? [],
            'live_in' => $this->live_in,
            'free_trial_day' => $this->free_trial_day,
            'is_available' => $this->is_available,
            'is_top' => $this->isTop(),
            'is_urgent' => $this->hasPaidUrgent(),
            'rating' => (float) $this->rating_avg,
            'reviews_count' => $this->reviews_count,
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'badges' => BadgeResource::collection($this->user->relationLoaded('activeBadges') ? $this->user->activeBadges : []),
            'published_at' => $this->published_at,
        ];
    }

    private function namesOf($place): array
    {
        return ['id' => $place->id, 'slug' => $place->slug, 'name' => ['uz' => $place->name_uz, 'ru' => $place->name_ru]];
    }
}
