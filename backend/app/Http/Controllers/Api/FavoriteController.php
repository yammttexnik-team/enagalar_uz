<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FavoriteRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\NannyProfile;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoriteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        // Faol bo'lmay qolgan yoki o'chirilgan e'lon/anketalar va sahifalash — bazada.
        $favorites = $request->user()->favorites()
            ->where(fn ($q) => $q
                ->where(fn ($n) => $n->where('favoritable_type', 'nanny')
                    ->whereIn('favoritable_id', NannyProfile::active()->select('id')))
                ->orWhere(fn ($v) => $v->where('favoritable_type', 'vacancy')
                    ->whereIn('favoritable_id', Vacancy::query()->where('status', 'active')->select('id'))))
            ->with(['favoritable' => fn (MorphTo $morph) => $morph->morphWith([
                NannyProfile::class => ['user.activeBadges', 'region', 'district', 'skills'],
                Vacancy::class => ['region', 'district', 'parentProfile.user.activeBadges'],
            ])])
            ->latest('id')->paginate(50);

        return FavoriteResource::collection($favorites);
    }

    public function store(FavoriteRequest $request): JsonResponse
    {
        $type = $request->validated('type');
        $model = $type === 'nanny' ? NannyProfile::active() : Vacancy::active();
        $target = $model->findOrFail($request->validated('id'));

        $favorite = $request->user()->favorites()->firstOrCreate([
            'favoritable_type' => $type,
            'favoritable_id' => $target->id,
        ]);

        return response()->json(['data' => ['type' => $type, 'id' => $target->id]], $favorite->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(FavoriteRequest $request): JsonResponse
    {
        $request->user()->favorites()
            ->where('favoritable_type', $request->validated('type'))
            ->where('favoritable_id', $request->validated('id'))
            ->delete();

        return response()->json(null, 204);
    }
}
