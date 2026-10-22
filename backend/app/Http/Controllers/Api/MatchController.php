<?php

namespace App\Http\Controllers\Api;

use App\Enums\NannyProfileStatus;
use App\Enums\VacancyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\MatchResource;
use App\Models\Vacancy;
use App\Services\Matching\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** AI moslashtirish natijalari (routes: has_role:parent / has_role:nanny). */
class MatchController extends Controller
{
    public function __construct(private readonly MatchingService $matching) {}

    /** E'lon uchun mos enagalar (faqat e'lon egasi, e'lon faol bo'lishi kerak). */
    public function forVacancy(Request $request, Vacancy $vacancy): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('viewMatches', $vacancy);

        if ($vacancy->status !== VacancyStatus::Active) {
            return response()->json(['message' => 'Moslashtirish faqat faol e\'lon uchun ishlaydi.'], 409);
        }

        return MatchResource::collection($this->matching->forVacancy($vacancy, $request->boolean('refresh')));
    }

    /** Enaga uchun mos e'lonlar (anketa faol bo'lishi kerak). */
    public function forNanny(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $profile = $request->user()->nannyProfile;

        if (! $profile || $profile->status !== NannyProfileStatus::Active) {
            return response()->json(['message' => 'Moslashtirish faqat faol anketa uchun ishlaydi.'], 409);
        }

        return MatchResource::collection($this->matching->forNanny($profile, $request->boolean('refresh')));
    }
}
