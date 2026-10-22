<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\LoginRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\TelegramLoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramAuthController extends Controller
{
    /** Kirish so'rovini yaratadi va botga havola qaytaradi. */
    public function start(Request $request): JsonResponse
    {
        // Kampaniya / taklif kodi ixtiyoriy; yaroqsiz bo'lsa jimgina e'tiborsiz qoldiriladi.
        $login = TelegramLoginRequest::issue(
            is_string($request->input('campaign')) ? $request->input('campaign') : null,
            is_string($request->input('referral')) ? $request->input('referral') : null,
        );

        return response()->json([
            'token' => $login->token,
            'bot_url' => $login->botUrl(),
            'expires_at' => $login->expires_at,
        ], 201);
    }

    /** Sayt bu endpointni bir necha soniyada bir so'raydi (polling). */
    public function status(string $token): JsonResponse
    {
        $login = TelegramLoginRequest::where('token', $token)->first();

        if (! $login || $login->status === LoginRequestStatus::Consumed || $login->isExpired() || $login->attemptsExhausted()) {
            return response()->json(['status' => 'expired'], 410);
        }

        if ($login->status === LoginRequestStatus::Pending) {
            return response()->json(['status' => 'pending']);
        }

        // Token faqat bot yuborgan kod bilan beriladi (confirm), polling hech qachon token qaytarmaydi.
        return response()->json(['status' => 'awaiting_code']);
    }

    /** Foydalanuvchi botdan olgan 6 xonali kodni kiritadi, to'g'ri bo'lsa Sanctum tokeni beriladi. */
    public function confirm(Request $request, string $token): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'digits:6']]);
        $login = TelegramLoginRequest::where('token', $token)->first();

        if (! $login || $login->status === LoginRequestStatus::Consumed || $login->isExpired() || $login->attemptsExhausted()) {
            return response()->json(['status' => 'expired'], 410);
        }

        if ($login->status === LoginRequestStatus::Pending) {
            return response()->json(['status' => 'pending'], 409);
        }

        if (! $login->checkCode($data['code'])) {
            if ($login->attemptsExhausted()) {
                return response()->json(['status' => 'expired'], 410);
            }

            return response()->json([
                'message' => __('Kod noto\'g\'ri.'),
                'errors' => ['code' => [__('Kod noto\'g\'ri.')]],
                'attempts_left' => TelegramLoginRequest::MAX_ATTEMPTS - $login->attempts,
            ], 422);
        }

        $user = $login->user;

        if ($user->isBlocked()) {
            return response()->json(['status' => 'blocked'], 403);
        }

        if (! $login->consume()) {
            return response()->json(['status' => 'expired'], 410);
        }

        return response()->json([
            'status' => 'approved',
            'access_token' => $user->createToken('web')->plainTextToken,
            'user' => new UserResource($user->load(['roles', 'activeBadges'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(status: 204);
    }
}
