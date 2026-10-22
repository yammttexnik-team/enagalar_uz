<?php

namespace App\Http\Controllers\Api;

use App\Enums\NannyProfileStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateNannyProfileRequest;
use App\Http\Resources\OwnNannyProfileResource;
use App\Models\NannyProfile;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Enaganing o'z anketasi (routes: has_role:nanny). */
class MyNannyProfileController extends Controller
{
    /** Tahrirlansa anketa qayta moderatsiyaga ketadigan erkin matn maydonlari. */
    private const MODERATED_FIELDS = ['bio'];

    public function show(Request $request): OwnNannyProfileResource
    {
        return $this->resource($this->profile($request));
    }

    public function update(UpdateNannyProfileRequest $request): OwnNannyProfileResource
    {
        $profile = $this->profile($request);
        $data = $request->validated();

        $fields = collect($data)->except('skills')->all();

        // Viloyat o'zgarib, tuman berilmasa — eski viloyatning tumani qolib ketmasin.
        if (array_key_exists('region_id', $fields) && (int) $fields['region_id'] !== (int) $profile->region_id && ! array_key_exists('district_id', $fields)) {
            $fields['district_id'] = null;
        }

        $profile->fill($fields);

        // Faol anketaning matni o'zgarsa — qayta moderatsiyaga (e'lonlardagi kabi).
        if ($profile->status === NannyProfileStatus::Active && $profile->isDirty(self::MODERATED_FIELDS)) {
            $profile->status = NannyProfileStatus::Pending;
        }

        $profile->save();

        if (array_key_exists('skills', $data)) {
            $profile->skills()->sync(Skill::whereIn('slug', $data['skills'])->pluck('id'));
        }

        return $this->resource($profile);
    }

    /** Anketani moderatsiyaga yuborish: minimal maydonlar to'ldirilgan bo'lishi kerak. */
    public function submit(Request $request): OwnNannyProfileResource
    {
        $profile = $this->profile($request);

        if (! in_array($profile->status, [NannyProfileStatus::Draft, NannyProfileStatus::Rejected], true)) {
            throw ValidationException::withMessages(['status' => ['Anketa allaqachon yuborilgan, faol yoki moderator tomonidan yashirilgan.']]);
        }

        Validator::make($profile->only(['region_id', 'bio', 'experience_years', 'price_amount', 'work_formats', 'languages']), [
            'region_id' => ['required'],
            'bio' => ['required', 'string', 'min:50'],
            'experience_years' => ['required', 'integer', 'min:0'],
            'price_amount' => ['required', 'integer', 'min:1'],
            'work_formats' => ['required', 'array', 'min:1'],
            'languages' => ['required', 'array', 'min:1'],
        ])->validate();

        $profile->update(['status' => NannyProfileStatus::Pending]);

        return $this->resource($profile);
    }

    public function availability(Request $request): OwnNannyProfileResource
    {
        $data = $request->validate(['is_available' => ['required', 'boolean']]);
        $profile = $this->profile($request);
        $profile->update($data);

        return $this->resource($profile);
    }

    private function profile(Request $request): NannyProfile
    {
        return $request->user()->nannyProfile()->firstOrCreate();
    }

    private function resource(NannyProfile $profile): OwnNannyProfileResource
    {
        return new OwnNannyProfileResource($profile->refresh()->load(['user.activeBadges', 'region', 'district', 'skills']));
    }
}
