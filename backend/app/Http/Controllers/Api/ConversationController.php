<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReportStatus;
use App\Events\ConversationRead;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReportConversationRequest;
use App\Http\Requests\Api\StartConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\Report;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    private const WITH = ['participants.activeBadges', 'participants.nannyProfile', 'participants.parentProfile', 'vacancy.parentProfile', 'latestMessage'];

    public function __construct(private ChatService $chat) {}

    /** Mening suhbatlarim (oxirgi xabar bo'yicha, xabarsiz suhbatlar ko'rsatilmaydi). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $me = $request->user();

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($q) => $q->whereKey($me->id))
            ->whereNotNull('last_message_at')
            ->with(self::WITH)
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->where('sender_id', '!=', $me->id)
                ->whereRaw('messages.id > coalesce((select cp.last_read_message_id from conversation_participants cp where cp.conversation_id = messages.conversation_id and cp.user_id = ?), 0)', [$me->id]),
            ])
            ->orderByDesc('last_message_at')->orderByDesc('id')
            ->simplePaginate(30);

        return ConversationResource::collection($conversations);
    }

    /** Suhbat boshlash yoki mavjudini ochish. */
    public function store(StartConversationRequest $request): JsonResponse
    {
        $conversation = $this->chat->start($request->user(), $request->validated());

        return $this->respond($conversation, $conversation->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Conversation $conversation): ConversationResource
    {
        $this->authorize('view', $conversation);

        return new ConversationResource($conversation->load(self::WITH));
    }

    /** O'qilgan oxirgi xabarni belgilaydi (faqat oshadi). */
    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $data = $request->validate([
            'message_id' => ['required', 'integer', 'exists:messages,id,conversation_id,'.$conversation->id],
        ]);

        $me = $request->user();
        $current = (int) $conversation->participants()->whereKey($me->id)->first()->pivot->last_read_message_id;

        if ($data['message_id'] > $current) {
            $conversation->participants()->updateExistingPivot($me->id, ['last_read_message_id' => $data['message_id']]);
            $this->chat->dispatch(new ConversationRead($conversation->id, $me->id, $data['message_id']));
            $this->chat->broadcastConversationUpdated($conversation, $me);
            $current = $data['message_id'];
        }

        return response()->json(['data' => [
            'conversation_id' => $conversation->id,
            'last_read_message_id' => $current ?: null,
            'unread_count' => $this->chat->unreadCount($conversation, $me),
        ]]);
    }

    public function mute(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $data = $request->validate(['muted' => ['required', 'boolean']]);

        $conversation->participants()->updateExistingPivot($request->user()->id, ['muted' => $data['muted']]);

        return $this->respond($conversation);
    }

    public function block(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $conversation->participants()->updateExistingPivot($request->user()->id, ['blocked_at' => now()]);

        return $this->respond($conversation);
    }

    public function unblock(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $conversation->participants()->updateExistingPivot($request->user()->id, ['blocked_at' => null]);

        return $this->respond($conversation);
    }

    /** Suhbat haqida shikoyat (moderator ko'rib chiqadi). */
    public function report(ReportConversationRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $report = Report::firstOrCreate(
            [
                'reporter_id' => $request->user()->id,
                'reportable_type' => $conversation->getMorphClass(),
                'reportable_id' => $conversation->id,
                'status' => ReportStatus::Open,
            ],
            $request->validated(),
        );

        return response()->json(['data' => ['id' => $report->id, 'status' => $report->status->value]], 201);
    }

    /** Sarlavhadagi belgi uchun jami o'qilmaganlar. */
    public function unread(Request $request): JsonResponse
    {
        return response()->json(['total' => $this->chat->totalUnread($request->user())]);
    }

    private function respond(Conversation $conversation, int $status = 200): JsonResponse
    {
        return (new ConversationResource($conversation->unsetRelation('participants')->load(self::WITH)))
            ->response()->setStatusCode($status);
    }
}
