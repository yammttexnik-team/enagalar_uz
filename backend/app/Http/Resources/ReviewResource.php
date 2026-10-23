<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sharh — ochiq ko'rinish. Muallif faqat "Dilnoza K." ko'rinishida chiqadi.
 *
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->text,
            'author' => $this->relationLoaded('author') ? ($this->author?->publicName() ?? '') : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
