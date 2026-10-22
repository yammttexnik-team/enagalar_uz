<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * Chat xabari. Suhbat (`conversation.participants`) yuklangan bo'lishi kerak.
 * Hodisalar uchun `neutral()`: ko'ruvchiga bog'liq maydon (is_mine) yo'q.
 *
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    /** Chat fayliga imzolangan havola amal qilish muddati (daqiqa). */
    public const URL_TTL_MINUTES = 60;

    private bool $neutral = false;

    /** Realtime hodisa uchun massiv (is_mine'siz). */
    public static function neutral(Message $message): array
    {
        $resource = new self($message);
        $resource->neutral = true;

        return $resource->resolve();
    }

    public function toArray(Request $request): array
    {
        $deleted = $this->trashed();

        $data = [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
        ];

        if (! $this->neutral) {
            $data['is_mine'] = $request->user()?->id === $this->sender_id;
        }

        return $data + [
            'type' => $this->type->value,
            'body' => $deleted ? null : $this->body,
            'attachment' => $deleted ? null : $this->attachment(),
            'reply_to' => $this->replyPreview(),
            'edited_at' => $this->edited_at?->toISOString(),
            'deleted' => $deleted,
            'created_at' => $this->created_at?->toISOString(),
            'read' => $this->isRead(),
            // Ichki kalitlar (mime, size, duration) chiqarilmaydi.
            'meta' => (object) array_filter(['contact_warning' => $this->meta['contact_warning'] ?? null]),
        ];
    }

    /** @return array{url: string, mime: string|null, size: int|null, duration?: int}|null */
    private function attachment(): ?array
    {
        if (! $this->attachment_path) {
            return null;
        }

        return array_filter([
            'url' => URL::temporarySignedRoute('chat-files', now()->addMinutes(self::URL_TTL_MINUTES), ['message' => $this->id]),
            'mime' => $this->meta['mime'] ?? null,
            'size' => $this->meta['size'] ?? null,
            'duration' => $this->meta['duration'] ?? null,
        ], fn ($v) => $v !== null);
    }

    /** @return array{id: int, preview: string, sender_id: int|null}|null */
    private function replyPreview(): ?array
    {
        $reply = $this->replyTo;

        return $reply ? [
            'id' => $reply->id,
            'preview' => $reply->preview(),
            'sender_id' => $reply->sender_id,
        ] : null;
    }

    /** Qabul qiluvchi (yuboruvchidan boshqa ishtirokchi) shu xabargacha o'qiganmi. */
    private function isRead(): bool
    {
        return $this->conversation->participants
            ->contains(fn ($p) => $p->id !== $this->sender_id && (int) $p->pivot->last_read_message_id >= $this->id);
    }
}
