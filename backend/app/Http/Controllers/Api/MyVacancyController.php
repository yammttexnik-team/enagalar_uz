<?php

namespace App\Http\Controllers\Api;

use App\Enums\VacancyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CloseVacancyRequest;
use App\Http\Requests\Api\VacancyRequest;
use App\Http\Resources\OwnVacancyResource;
use App\Models\ParentProfile;
use App\Models\Vacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/** Ota-onaning o'z e'lonlari (routes: has_role:parent). */
class MyVacancyController extends Controller
{
    /** Bir ota-onada faol + moderatsiyadagi e'lonlar soni chegarasi. */
    public const MAX_OPEN = 5;

    /** Matn maydonlari o'zgarsa, faol e'lon qayta moderatsiyaga tushadi. */
    private const MODERATED_FIELDS = ['title', 'description', 'schedule'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $vacancies = $this->profile($request)->vacancies()
            ->with(['region', 'district'])->withCount('applications')
            ->latest('id')->paginate(20);

        return OwnVacancyResource::collection($vacancies);
    }

    /** Bitta o'z e'lonim (ro'yxatdagi bilan bir xil shakl). */
    public function show(Vacancy $vacancy): OwnVacancyResource
    {
        $this->authorize('update', $vacancy);

        return new OwnVacancyResource($vacancy->load(['region', 'district'])->loadCount('applications'));
    }

    public function store(VacancyRequest $request): JsonResponse
    {
        $profile = $this->profile($request);
        $this->ensureLimit($profile);

        $vacancy = $profile->vacancies()->create($request->validated() + ['status' => VacancyStatus::Pending]);

        return (new OwnVacancyResource($vacancy->load(['region', 'district'])))->response()->setStatusCode(201);
    }

    public function update(VacancyRequest $request, Vacancy $vacancy): OwnVacancyResource
    {
        $this->authorize('update', $vacancy);

        $data = $request->validated();

        if ($vacancy->status === VacancyStatus::Active && $vacancy->fill($data)->isDirty(self::MODERATED_FIELDS)) {
            $vacancy->status = VacancyStatus::Pending;
        } elseif (in_array($vacancy->status, [VacancyStatus::Draft, VacancyStatus::Rejected], true)) {
            // Rad etilgan e'lonni tahrirlab qayta yuborish — yana moderatsiyaga.
            $this->ensureLimit($vacancy->parentProfile);
            $vacancy->status = VacancyStatus::Pending;
        }

        $vacancy->fill($data)->save();

        return new OwnVacancyResource($vacancy->load(['region', 'district']));
    }

    public function destroy(Vacancy $vacancy): JsonResponse
    {
        $this->authorize('delete', $vacancy);
        $vacancy->delete();

        return response()->json(null, 204);
    }

    public function close(CloseVacancyRequest $request, Vacancy $vacancy): OwnVacancyResource
    {
        $this->authorize('close', $vacancy);

        $vacancy->update(['status' => $request->boolean('found') ? VacancyStatus::Found : VacancyStatus::Closed]);

        return new OwnVacancyResource($vacancy->load(['region', 'district']));
    }

    private function profile(Request $request): ParentProfile
    {
        return $request->user()->parentProfile()->firstOrCreate();
    }

    private function ensureLimit(ParentProfile $profile): void
    {
        $open = $profile->vacancies()->whereIn('status', [VacancyStatus::Active, VacancyStatus::Pending])->count();

        if ($open >= self::MAX_OPEN) {
            throw ValidationException::withMessages([
                'vacancy' => ['Bir vaqtda ko\'pi bilan '.self::MAX_OPEN.' ta faol yoki moderatsiyadagi e\'lon bo\'lishi mumkin.'],
            ]);
        }
    }
}
