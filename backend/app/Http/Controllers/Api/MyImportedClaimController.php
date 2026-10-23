<?php

namespace App\Http\Controllers\Api;

use App\Enums\ImportedListingStatus;
use App\Enums\Role;
use App\Enums\VacancyStatus;
use App\Enums\WorkFormat;
use App\Http\Controllers\Controller;
use App\Http\Resources\ImportedListingResource;
use App\Models\ImportedListing;
use App\Models\NannyProfile;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * "Topilgan e'lonlar": foydalanuvchining telefoni yoki Telegram username'i Telegram kanaldagi e'lon
 * kontaktiga mos kelsa, u e'lonni tasdiqlab o'ziniki qiladi yoki o'chiradi.
 */
class MyImportedClaimController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $listings = ImportedListing::claimableBy($request->user())->with(['region', 'district'])
            ->latest('id')->get();

        return ImportedListingResource::collection($listings);
    }

    /** Tasdiqlash: vakansiya → yangi e'lon (moderatsiyaga), anketa → bo'sh maydonlar to'ldiriladi. */
    public function accept(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $result = DB::transaction(function () use ($user, $id) {
            $listing = $this->claimable($user, $id);
            $topUntil = now()->addDays((int) config('imports.claim_top_days'));

            if ($listing->type === ImportedListing::TYPE_VACANCY) {
                $target = $this->createVacancy($user, $listing);
                $payload = ['type' => 'vacancy', 'id' => $target->id];
            } else {
                $target = $this->fillNannyProfile($user, $listing);
                $payload = ['type' => 'nanny', 'id' => $target->id];
            }

            // Sovg'a: TOP (ustun fillable emas — to'g'ridan-to'g'ri yoziladi)
            $target->top_until = $topUntil;
            $target->save();

            $listing->update([
                'status' => ImportedListingStatus::Claimed,
                'claimed_by_user_id' => $user->id,
                'claimed_at' => now(),
                'archived_at' => now(),
                'archive_reason' => 'claimed',
            ]);

            return $payload + ['top_until' => $topUntil];
        });

        return response()->json(['data' => $result, 'message' => "E'lon sizniki bo'ldi."]);
    }

    /** "Bu meniki emas" — boshqa ko'rsatilmaydi; "O'chirish" — saytdan olib tashlanadi. */
    public function dismiss(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['not_mine', 'remove'])]]);

        $listing = $this->claimable($request->user(), $id);

        $listing->update($data['action'] === 'remove'
            ? [
                'status' => ImportedListingStatus::Removed,
                'removal_requested_at' => now(),
                'archived_at' => now(),
                'archive_reason' => 'removal_request',
            ]
            : ['claim_dismissed_at' => now()]);

        return response()->json(null, 204);
    }

    /** E'lon foydalanuvchiga mos kelmasa (yoki allaqachon tasdiqlangan bo'lsa) — 403. */
    private function claimable(User $user, int $id): ImportedListing
    {
        $listing = ImportedListing::whereKey($id)->lockForUpdate()->firstOrFail();

        $open = in_array($listing->status, [ImportedListingStatus::Active, ImportedListingStatus::Pending], true)
            && $listing->claimed_by_user_id === null && $listing->claim_dismissed_at === null;

        abort_unless($open && $listing->matchesUser($user), 403, "Bu e'lon sizning kontaktingizga mos kelmaydi.");

        return $listing;
    }

    private function createVacancy(User $user, ImportedListing $listing): Vacancy
    {
        if (! $listing->region_id) {
            throw ValidationException::withMessages([
                'region_id' => ["E'londa hudud aniqlanmadi. Iltimos, e'lonni kabinetdan o'zingiz joylang."],
            ]);
        }

        $user->assignRole(Role::Parent->value);
        $profile = $user->parentProfile()->firstOrCreate();

        return $profile->vacancies()->create([
            'region_id' => $listing->region_id,
            'district_id' => $listing->district_id,
            'title' => $listing->title ?: 'Enaga kerak',
            'description' => $listing->description ?: ($listing->title ?: 'Enaga kerak'),
            'work_format' => $listing->work_format ?? WorkFormat::FullTime,
            'schedule' => $listing->schedule,
            'salary_min' => $listing->price_min,
            'salary_max' => $listing->price_max,
            'salary_unit' => $listing->price_unit?->value ?? 'month',
            'children_count' => $listing->children_count ?? 1,
            'children_ages' => $listing->children_ages,
            'languages' => $listing->languages,
            'live_in' => $listing->live_in,
            'is_urgent' => $listing->is_urgent,
            // Oddiy moderatsiya: admin tasdiqlagandan keyin faol bo'ladi
            'status' => VacancyStatus::Pending,
        ]);
    }

    /** Faqat bo'sh maydonlar to'ldiriladi: enaga o'zi kiritgan ma'lumot hech qachon almashtirilmaydi. */
    private function fillNannyProfile(User $user, ImportedListing $listing): NannyProfile
    {
        $user->assignRole(Role::Nanny->value);
        $profile = $user->nannyProfile()->firstOrCreate();

        $empty = fn (string $field) => $profile->{$field} === null || $profile->{$field} === [] || $profile->{$field} === '';

        // Hudud va tuman birga: anketada hudud bo'sh bo'lsagina.
        if ($listing->region_id && $profile->region_id === null) {
            $profile->region_id = $listing->region_id;
            $profile->district_id = $listing->district_id;
        }

        $candidates = [
            'bio' => $listing->description,
            'birth_year' => $listing->person_age ? (int) now()->year - $listing->person_age : null,
            'work_formats' => $listing->work_format ? [$listing->work_format->value] : null,
            'languages' => $listing->languages,
            'age_groups' => $listing->age_groups,
        ];

        foreach ($candidates as $field => $value) {
            if ($value !== null && $value !== [] && $empty($field)) {
                $profile->{$field} = $value;
            }
        }

        // Standart qiymatli ustunlar: 0 tajriba va narxsiz anketa — "bo'sh" hisoblanadi.
        if ($listing->experience_years && ! $profile->experience_years) {
            $profile->experience_years = $listing->experience_years;
        }
        if ($listing->price_min && $profile->price_amount === null) {
            $profile->price_amount = $listing->price_min;
            if ($listing->price_unit) {
                $profile->price_unit = $listing->price_unit;
            }
        }
        if ($listing->live_in && ! $profile->live_in) {
            $profile->live_in = true;
        }

        $profile->save();

        return $profile;
    }
}
