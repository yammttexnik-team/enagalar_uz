<?php

namespace App\Http\Controllers\Api;

use App\Enums\NannyProfileStatus;
use App\Http\Controllers\Controller;
use App\Models\NannyProfile;
use App\Models\Vacancy;
use App\Services\Bonus\InsufficientBonus;
use App\Services\Bonus\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Pullik xizmatlar (TOP, "Shoshilinch"): narxlar va bonus bilan sotib olish. */
class PromotionController extends Controller
{
    public function __construct(private PromotionService $promotions) {}

    public function prices(): JsonResponse
    {
        return response()->json(['data' => $this->promotions->prices()]);
    }

    /** Faqat egasi va faqat faol (e'lon qilingan) e'lon/anketa uchun. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target' => ['required', Rule::in(['vacancy', 'nanny'])],
            'id' => ['required', 'integer'],
            'service' => ['required', Rule::in(PromotionService::SERVICES)],
        ]);

        $user = $request->user();

        if ($data['target'] === 'vacancy') {
            $target = Vacancy::findOrFail($data['id']);
            abort_unless($target->parentProfile?->user_id === $user->id, 403);
            $active = Vacancy::active()->whereKey($target->id)->exists();
        } else {
            $target = NannyProfile::findOrFail($data['id']);
            abort_unless($target->user_id === $user->id, 403);
            $active = $target->status === NannyProfileStatus::Active;
        }

        if (! $active) {
            throw ValidationException::withMessages(['id' => ["Xizmat faqat faol (e'lon qilingan) e'lon yoki anketa uchun sotib olinadi."]]);
        }

        try {
            $target = $this->promotions->buy($user, $target, $data['service']);
        } catch (InsufficientBonus) {
            $price = number_format($this->promotions->price($data['service']), 0, '.', ' ');

            throw ValidationException::withMessages(['service' => ["Bonus yetarli emas: bu xizmat $price so'm turadi."]]);
        }

        return response()->json(['data' => [
            'target' => $data['target'],
            'id' => $target->id,
            'service' => $data['service'],
            'top_until' => $target->top_until,
            'urgent_until' => $target->urgent_until,
            'balance' => $user->bonusBalance(),
        ]]);
    }
}
