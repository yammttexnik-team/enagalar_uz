<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageDeleted;
use App\Events\MessageUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    public function __construct(private ChatService $chat) {}

    /** Xabarlar (eskidan yangiga). before_id dan oldingi `limit` ta xabar. */
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $data = $request->validate([
            'before_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $limit = (int) ($data['limit'] ?? 30);

        $conversation->load('participants');

        $rows = $conversation->messages()->withTrashed()
            ->with(['replyTo' => fn ($q) => $q->withTrashed()])
            ->when($data['before_id'] ?? null, fn ($q, $id) => $q->where('id', '<', $id))
            ->orderByDesc('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $messages = $rows->take($limit)->reverse()->values();
        $messages->each(fn (Message $m) => $m->setRelation('conversation', $conversation));

        return MessageResource::collection($messages)
            ->additional(['meta' => ['has_more' => $hasMore]])
            ->response();
    }

    public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $me = $request->user();
        $conversation->load('participants');
        abort_if($conversation->participants->contains(fn ($p) => $p->pivot->blocked_at), 403, 'Bu suhbatda xabar yuborib bo\'lmaydi (bloklangan).');

        $message = $this->chat->send($conversation, $me, $request->validated(), $request->file('file'));

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    /** O'z matnli xabarini 24 soat ichida tahrirlash. */
    public function update(Request $request, Message $message): MessageResource
    {
        $this->authorize('update', $message);

        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);

        if ($message->type->value !== 'text') {
            throw ValidationException::withMessages(['body' => ['Faqat matnli xabarni tahrirlash mumkin.']]);
        }

        if ($message->created_at->lt(now()->subHours(ChatService::EDIT_WINDOW_HOURS))) {
            throw ValidationException::withMessages(['body' => ['Tahrirlash muddati (24 soat) o\'tgan.']]);
        }

        $message->update(['body' => trim($data['body']), 'edited_at' => now()]);

        $this->prepare($message);
        $this->chat->dispatch(new MessageUpdated($message));
        $this->refreshListIfLatest($message);

        return new MessageResource($message);
    }

    /** O'z xabarini hamma uchun o'chirish (fayl ham diskdan o'chadi). */
    public function destroy(Message $message): JsonResponse
    {
        $this->authorize('delete', $message);

        if ($message->attachment_path) {
            Storage::disk(Message::DISK)->delete($message->attachment_path);
            $message->forceFill(['attachment_path' => null])->save();
        }

        $message->delete();

        $this->prepare($message);
        $this->chat->dispatch(new MessageDeleted($message));
        $this->refreshListIfLatest($message, true);

        return response()->json(['data' => ['id' => $message->id, 'deleted' => true]]);
    }

    private function prepare(Message $message): void
    {
        $message->setRelation('conversation', $message->conversation()->with('participants')->first());
        $message->load(['replyTo' => fn ($q) => $q->withTrashed()]);
    }

    /** Oxirgi xabar o'zgargan/o'chgan bo'lsa, qabul qiluvchining ro'yxatini yangilaydi. */
    private function refreshListIfLatest(Message $message, bool $force = false): void
    {
        $conversation = $message->conversation;
        $latestId = $conversation->messages()->max('id');

        if (! $force && (int) $latestId !== $message->id) {
            return;
        }

        foreach ($conversation->participants as $participant) {
            if ($participant->id !== $message->sender_id) {
                $this->chat->broadcastConversationUpdated($conversation, $participant);
            }
        }
    }
}
