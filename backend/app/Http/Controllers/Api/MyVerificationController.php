<?php

namespace App\Http\Controllers\Api;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IdentityVerificationRequest;
use App\Models\Badge;
use App\Models\Consent;
use App\Models\IdentityVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Foydalanuvchi o'z tasdiqlash so'rovlarini yuboradi. Maxfiy ma'lumotlar javobda hech qachon qaytmaydi. */
class MyVerificationController extends Controller
{
    /** 24 soatda yuborilishi mumkin bo'lgan pasport so'rovlari soni. */
    public const MAX_IDENTITY_PER_DAY = 3;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $identity = $user->identityVerifications()->latest('id')->first();

        return response()->json(['data' => [
            'identity' => [
                'status' => $identity?->status->value ?? 'none',
                'rejection_reason' => $identity?->rejection_reason,
                'submitted_at' => $identity?->created_at?->toIso8601String(),
                'verified' => $user->isVerified(),
            ],
            // Admin tasdiqlagan ma'lumotlar: is_public null — "profilda ko'rsatilsinmi?" javobi kutilmoqda.
            'badges' => $user->validBadges()->orderBy('sort')->get()->map(fn (Badge $badge) => $this->badge($badge))->values(),
        ]]);
    }

    /** Tasdiqlangan ma'lumotni profilda ko'rsatish yoki yashirish. */
    public function updateBadge(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate(['is_public' => ['required', 'boolean']]);
        $user = $request->user();

        $badge = $user->validBadges()->where('slug', $slug)->first();
        abort_unless($badge, 404);

        $user->badges()->updateExistingPivot($badge->id, [
            'is_public' => $data['is_public'],
            'answered_at' => now(),
        ]);

        return response()->json(['data' => $this->badge($user->validBadges()->where('slug', $slug)->first())]);
    }

    private function badge(Badge $badge): array
    {
        return [
            'slug' => $badge->slug,
            'name' => ['uz' => $badge->name_uz, 'ru' => $badge->name_ru],
            'description' => ['uz' => $badge->description_uz, 'ru' => $badge->description_ru],
            'icon' => $badge->icon,
            'color' => $badge->color,
            'is_public' => $badge->pivot->is_public,
            'granted_at' => $badge->pivot->granted_at?->toIso8601String(),
            'expires_at' => $badge->pivot->expires_at?->toIso8601String(),
        ];
    }

    public function storeIdentity(IdentityVerificationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isVerified()) {
            return $this->error('Siz allaqachon tasdiqlangansiz.', 409);
        }
        if ($user->identityVerifications()->where('status', VerificationStatus::Pending)->exists()) {
            return $this->error('Oldingi so\'rovingiz hali ko\'rib chiqilmoqda.', 409);
        }
        $recent = $user->identityVerifications()->where('created_at', '>=', now()->subDay())->count();
        if ($recent >= self::MAX_IDENTITY_PER_DAY) {
            return $this->error('Kuniga 3 martadan ko\'p so\'rov yuborib bo\'lmaydi. Keyinroq urinib ko\'ring.', 429);
        }

        $path = $this->storeFile($request->file('photo'), "identity/{$user->id}");

        DB::transaction(function () use ($request, $user, $path) {
            $user->identityVerifications()->create([
                'full_name' => $request->validated('full_name'),
                'passport_series' => $request->validated('passport_series'),
                'passport_number' => $request->validated('passport_number'),
                'pinfl' => $request->validated('pinfl'),
                'birth_date' => $request->validated('birth_date'),
                'document_path' => $path,
                'status' => VerificationStatus::Pending,
            ]);
            $this->recordConsent($request, Consent::PASSPORT);
        });

        return response()->json(['data' => [
            'status' => VerificationStatus::Pending->value,
            'submitted_at' => now()->toIso8601String(),
        ]], 201);
    }

    private function storeFile($file, string $dir): string
    {
        $name = Str::random(40).'.'.$file->guessExtension();

        return Storage::disk(IdentityVerification::DISK)->putFileAs($dir, $file, $name);
    }

    private function recordConsent(Request $request, string $type): void
    {
        $request->user()->consents()->create([
            'type' => $type,
            'version' => config('enagalar.consent_version', '2026-10'),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'accepted_at' => now(),
        ]);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
