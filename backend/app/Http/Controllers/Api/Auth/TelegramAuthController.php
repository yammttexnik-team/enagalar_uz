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
    public function start(): JsonResponse
    {
        $login = TelegramLoginRequest::issue();

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

        if (! $login || $login->status === LoginRequestStatus::Consumed || $login->isExpired()) {
            return response()->json(['status' => 'expired'], 410);
        }

        if ($login->status === LoginRequestStatus::Pending) {
            return response()->json(['status' => 'pending']);
        }

        $login->update(['status' => LoginRequestStatus::Consumed]);
        $user = $login->user;

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
