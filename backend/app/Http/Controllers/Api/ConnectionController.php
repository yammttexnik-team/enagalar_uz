<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Connection;
use App\Models\Review;
use App\Models\User;
use App\Services\Rating\ConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Bog'lanishlar: "Birga ishladingizmi?" savollari va baholash. */
class ConnectionController extends Controller
{
    public function __construct(private ConnectionService $connections) {}

    /** So'ralgan bog'lanishlarim (javob kutilayotgan, baholash mumkin bo'lgan va yakunlanganlar). */
    public function index(Request $request): JsonResponse
    {
        $me = $request->user();

        $items = Connection::query()->involving($me)
            ->whereNotNull('asked_at')
            ->with(['parent.nannyProfile', 'nanny.nannyProfile', 'reviews'])
            ->latest('asked_at')
            ->limit(50)
            ->get()
            ->map(fn (Connection $c) => $this->present($c, $me));

        return response()->json([
            'data' => $items->values(),
            'meta' => [
                // Kabinet kartasi uchun: javob yoki baho kutilayotganlar
                'pending_count' => $items->whereIn('status', ['question', 'can_review'])->count(),
            ],
        ]);
    }

    public function answer(Request $request, Connection $connection): JsonResponse
    {
        $data = $request->validate(['worked' => ['required', 'boolean']]);

        $connection = $this->connections->answer($connection, $request->user(), (bool) $data['worked']);
        $connection->load(['parent.nannyProfile', 'nanny.nannyProfile', 'reviews']);

        return response()->json(['data' => $this->present($connection, $request->user())]);
    }

    public function review(Request $request, Connection $connection): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = $this->connections->review($connection, $request->user(), (int) $data['rating'], $data['comment'] ?? null);

        return (new ReviewResource($review->load('author')))->response()->setStatusCode(201);
    }

    /** Ko'ruvchi nuqtai nazaridan bog'lanish holati. */
    private function present(Connection $c, User $me): array
    {
        $side = $c->sideOf($me);
        $otherSide = Connection::otherSide($side);
        $other = $c->userOf($otherSide);
        $myAnswer = $c->answerOf($side);
        $myReview = $c->reviews->firstWhere('author_id', $me->id);

        $status = match (true) {
            $myAnswer === null => 'question',
            $c->parent_worked === false || $c->nanny_worked === false => 'not_worked',
            ! $c->bothWorked() => 'waiting',
            $myReview === null => 'can_review',
            default => 'reviewed',
        };

        return [
            'id' => $c->id,
            'status' => $status,
            'my_role' => $side,
            'other' => $other ? [
                'id' => $other->id,
                'name' => $other->publicName(),
                'role' => $otherSide,
                'nanny_profile_id' => $otherSide === 'nanny' ? $other->nannyProfile?->id : null,
            ] : null,
            'conversation_id' => $c->conversation_id,
            'my_answer' => $myAnswer,
            'connected_at' => $c->connected_at?->toISOString(),
            'my_review' => $myReview ? [
                'rating' => $myReview->rating,
                'comment' => $myReview->text,
                'hidden' => $myReview->status !== Review::PUBLISHED,
            ] : null,
        ];
    }
}
