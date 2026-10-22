<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApplyRequest;
use App\Http\Requests\Api\UpdateApplicationStatusRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    /** Enaga e'longa ariza yuboradi (faqat faol e'lon va faol anketa bilan). */
    public function apply(ApplyRequest $request, int $id): JsonResponse
    {
        $vacancy = Vacancy::active()->with('parentProfile')->find($id)
            ?? throw ValidationException::withMessages(['vacancy' => ['E\'lon faol emas yoki topilmadi.']]);

        $profile = $request->user()->nannyProfile;

        if (! $profile || $profile->status->value !== 'active') {
            throw ValidationException::withMessages(['nanny_profile' => ['Ariza yuborish uchun anketangiz faol (tasdiqlangan) bo\'lishi kerak.']]);
        }

        if ($vacancy->parentProfile->user_id === $request->user()->id) {
            throw ValidationException::withMessages(['vacancy' => ['O\'z e\'loningizga ariza yubora olmaysiz.']]);
        }

        if ($vacancy->applications()->where('nanny_profile_id', $profile->id)->exists()) {
            return response()->json(['message' => 'Bu e\'longa ariza allaqachon yuborilgan.'], 409);
        }

        $application = $vacancy->applications()->create([
            'nanny_profile_id' => $profile->id,
            'message' => $request->validated('message'),
            'status' => ApplicationStatus::Sent,
        ]);

        return (new ApplicationResource($application))->response()->setStatusCode(201);
    }

    /** Ota-onaga: o'z e'loniga kelgan arizalar. */
    public function forVacancy(Vacancy $vacancy): AnonymousResourceCollection
    {
        $this->authorize('viewApplications', $vacancy);

        $applications = $vacancy->applications()
            ->with(['nannyProfile.user.activeBadges', 'nannyProfile.region', 'nannyProfile.district', 'nannyProfile.skills'])
            ->latest('id')->paginate(20);

        return ApplicationResource::collection($applications);
    }

    /** Enagaga: o'zi yuborgan arizalar. */
    public function mine(Request $request): AnonymousResourceCollection
    {
        $applications = Application::where('nanny_profile_id', $request->user()->nannyProfile?->id)
            ->with(['vacancy' => fn ($q) => $q->withTrashed(), 'vacancy.region', 'vacancy.district', 'vacancy.parentProfile.user.activeBadges'])
            ->latest('id')->paginate(20);

        return ApplicationResource::collection($applications);
    }

    public function updateStatus(UpdateApplicationStatusRequest $request, Application $application): ApplicationResource
    {
        $this->authorize('update', $application);

        $to = ApplicationStatus::from($request->validated('status'));

        if (! $application->status->canTransitionTo($to)) {
            throw ValidationException::withMessages(['status' => ['Bu holatga o\'tish mumkin emas.']]);
        }

        $application->update(['status' => $to]);

        return new ApplicationResource($application);
    }
}
