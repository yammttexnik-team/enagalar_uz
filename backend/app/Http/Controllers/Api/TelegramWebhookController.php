<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramLoginHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramLoginHandler $handler): JsonResponse
    {
        $secret = (string) config('services.telegram.webhook_secret');

        abort_unless(
            $secret !== '' && hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')),
            403,
        );

        $handler->handle($request->all());

        return response()->json(['ok' => true]);
    }
}
