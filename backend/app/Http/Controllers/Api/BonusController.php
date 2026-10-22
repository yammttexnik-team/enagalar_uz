<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Foydalanuvchining o'z bonus hamyoni (faqat o'zinikini ko'radi). */
class BonusController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $page = $user->bonusTransactions()->latest('id')->paginate(20);

        return response()->json([
            'balance' => $user->bonusBalance(),
            'referral_code' => $user->referralCode(),
            'referral_link' => $user->referralLink(),
            'transactions' => [
                'data' => $page->getCollection()->map(fn ($t) => [
                    'id' => $t->id,
                    'amount' => $t->amount,
                    'type' => $t->type->value,
                    'description' => $t->description,
                    'expires_at' => $t->expires_at,
                    'created_at' => $t->created_at,
                ])->values(),
                'meta' => ['total' => $page->total(), 'per_page' => $page->perPage(), 'current_page' => $page->currentPage(), 'last_page' => $page->lastPage()],
            ],
        ]);
    }
}
