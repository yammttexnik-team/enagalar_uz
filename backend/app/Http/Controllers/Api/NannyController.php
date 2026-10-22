<?php

namespace App\Http\Controllers\Api;

use App\Enums\PriceUnit;
use App\Http\Controllers\Concerns\CountsViews;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchNanniesRequest;
use App\Http\Resources\NannyProfileResource;
use App\Models\NannyProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NannyController extends Controller
{
    use CountsViews;

    /** Faol anketalar bo'yicha qidiruv. Viloyat/tuman — slug orqali. */
    public function index(SearchNanniesRequest $request): AnonymousResourceCollection
    {
        $f = $request->validated();

        $query = NannyProfile::active()->withPublic()
            ->when($f['region'] ?? null, fn (Builder $q, $v) => $q->whereHas('region', fn ($r) => $r->where('slug', $v)))
            ->when($f['district'] ?? null, fn (Builder $q, $v) => $q->whereHas('district', fn ($r) => $r->where('slug', $v)))
            ->when($f['work_format'] ?? null, fn (Builder $q, $v) => $q->whereJsonContains('work_formats', $v))
            ->when($f['language'] ?? null, fn (Builder $q, $v) => $q->whereJsonContains('languages', $v))
            ->when($f['age_group'] ?? null, fn (Builder $q, $v) => $q->whereJsonContains('age_groups', $v))
            ->when($f['skill'] ?? null, fn (Builder $q, $v) => $q->whereHas('skills', fn ($s) => $s->where('slug', $v)))
            // Narx faqat bir xil birlikda solishtiriladi (standart: oyiga).
            ->when($f['price_max'] ?? null, fn (Builder $q, $v) => $q
                ->where('price_unit', $f['price_unit'] ?? PriceUnit::Month->value)
                ->where('price_amount', '<=', $v))
            ->when($f['experience_min'] ?? null, fn (Builder $q, $v) => $q->where('experience_years', '>=', $v))
            ->when($request->boolean('live_in'), fn (Builder $q) => $q->where('live_in', true))
            ->when($request->boolean('available_only'), fn (Builder $q) => $q->where('is_available', true))
            ->when($request->boolean('verified_only'), fn (Builder $q) => $this->hasBadge($q, 'verified'))
            ->when($f['badge'] ?? null, fn (Builder $q, $v) => $this->hasBadge($q, $v));

        match ($f['sort'] ?? 'newest') {
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('reviews_count'),
            'price_asc' => $query->orderBy('price_amount'),
            'experience' => $query->orderByDesc('experience_years'),
            default => $query->orderByDesc('published_at'),
        };

        return NannyProfileResource::collection($query->orderByDesc('id')->paginate(20)->withQueryString());
    }

    public function show(Request $request, int $id): NannyProfileResource
    {
        $profile = NannyProfile::active()->withPublic()->findOrFail($id);
        $this->countView($request, $profile, $profile->user_id);

        return new NannyProfileResource($profile);
    }

    /** Foydalanuvchida muddati o'tmagan faol nishon borligini tekshiradi. */
    private function hasBadge(Builder $query, string $slug): void
    {
        $query->whereHas('user.badges', fn ($b) => $b
            ->where('badges.slug', $slug)
            ->where('badges.is_active', true)
            ->where(fn ($e) => $e->whereNull('user_badges.expires_at')->orWhere('user_badges.expires_at', '>', now())));
    }
}
