<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\LoginRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Campaign;
use App\Models\TelegramLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /**
     * Telegram Login vidjeti (oauth.telegram.org): Telegram imzolagan ma'lumot bot tokeni bilan tekshiriladi.
     * Telefon raqami kelmaydi — kerak bo'lsa keyin bot orqali tasdiqlanadi (oddiy kirish oqimi).
     */
    public function widget(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:64'],
            'photo_url' => ['nullable', 'string', 'max:1024'],
            'auth_date' => ['required', 'integer'],
            'hash' => ['required', 'string', 'size:64'],
            'campaign' => ['nullable', 'string', 'max:32'],
            'referral' => ['nullable', 'string', 'max:16'],
        ]);

        $signed = $request->only(['id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date']);
        if (! self::validWidgetHash($signed, $data['hash']) || (int) $data['auth_date'] < now()->subDay()->timestamp) {
            return response()->json(['message' => __('Telegram maʼlumoti tasdiqlanmadi.')], 422);
        }

        $user = User::where('telegram_id', $data['id'])->first();
        if ($user?->isBlocked()) {
            return response()->json(['status' => 'blocked'], 403);
        }

        $user = DB::transaction(function () use ($user, $data) {
            $isNew = ! $user;
            $user ??= new User(['locale' => app()->getLocale() === 'ru' ? 'ru' : 'uz']);
            $user->fill([
                'name' => $user->name ?: trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
                'telegram_id' => $data['id'],
                'telegram_username' => $data['username'] ?? $user->telegram_username,
            ]);
            if ($isNew) {
                $campaign = Campaign::normalizeCode($data['campaign'] ?? null);
                $campaign = $campaign ? Campaign::where('code', $campaign)->first() : null;
                if ($campaign && $campaign->isRunning() && $campaign->hasRoom()) {
                    $user->campaign_id = $campaign->id;
                }
                $referral = User::normalizeReferralCode($data['referral'] ?? null);
                if ($referral && ($referrer = User::where('referral_code', $referral)->first())) {
                    $user->referred_by_id = $referrer->id;
                }
            }
            $user->save();

            return $user;
        });

        return response()->json([
            'status' => 'approved',
            'access_token' => $user->createToken('web')->plainTextToken,
            'user' => new UserResource($user->load(['roles', 'activeBadges'])),
        ]);
    }

    /** https://core.telegram.org/widgets/login#checking-authorization */
    public static function validWidgetHash(array $fields, string $hash): bool
    {
        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            return false;
        }

        $fields = array_filter($fields, fn ($v) => $v !== null && $v !== '');
        ksort($fields);
        $check = implode("\n", array_map(fn ($k, $v) => "$k=$v", array_keys($fields), $fields));

        return hash_equals(hash_hmac('sha256', $check, hash('sha256', $token, true)), strtolower($hash));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(status: 204);
    }
}
