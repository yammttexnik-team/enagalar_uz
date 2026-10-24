<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Telegram kanaldan olingan e'lon — ochiq ko'rinish.
 * Kontakt yo'q: raw_text va contact_hashes hech qachon qaytmaydi, faqat asl postga havola.
 */
class ImportedListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'region' => $this->whenLoaded('region', fn () => $this->region ? ['id' => $this->region->id, 'slug' => $this->region->slug, 'name' => ['uz' => $this->region->name_uz, 'ru' => $this->region->name_ru]] : null),
            'district' => $this->whenLoaded('district', fn () => $this->district ? ['id' => $this->district->id, 'slug' => $this->district->slug, 'name' => ['uz' => $this->district->name_uz, 'ru' => $this->district->name_ru]] : null),
            'work_format' => $this->work_format?->value,
            'schedule' => $this->schedule,
            'price' => ['min' => $this->price_min, 'max' => $this->price_max, 'unit' => $this->price_unit?->value],
            'children_count' => $this->children_count,
            'children_ages' => $this->children_ages ?? [],
            'age_groups' => $this->age_groups ?? [],
            'languages' => $this->languages ?? [],
            'experience_years' => $this->experience_years,
            'person_age' => $this->person_age,
            'live_in' => $this->live_in,
            'is_urgent' => $this->is_urgent,
            'source' => [
                // Kanal nomi o'zgargan bo'lishi mumkin: havoladagi nom ustun (t.me/<kanal>/<post>)
                'channel' => preg_match('~t\.me/(?:s/)?([A-Za-z0-9_]+)/\d+~', (string) $this->source_url, $m) ? $m[1] : $this->source_channel,
                'url' => $this->source_url,
                'posted_at' => $this->source_posted_at,
            ],
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
        ];
    }
}
