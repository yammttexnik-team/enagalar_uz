<?php

namespace App\Models;

use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['conversation_id', 'sender_id', 'type', 'body', 'attachment_path', 'meta', 'reply_to_id', 'edited_at'])]
class Message extends Model
{
    use SoftDeletes;

    /** Chat fayllari (rasm, ovoz) saqlanadigan yopiq disk. */
    public const DISK = 'private';

    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'meta' => 'array',
            'edited_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Message $message) {
            $message->conversation()->update(['last_message_at' => $message->created_at]);
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    /** Ro'yxat va bildirishnomalar uchun qisqa matn. */
    public function preview(): string
    {
        if ($this->trashed()) {
            return 'Xabar o\'chirildi';
        }

        return match ($this->type) {
            MessageType::Image => '📷 Rasm',
            MessageType::Voice => '🎤 Ovozli xabar',
            MessageType::File => '📎 Fayl',
            default => Str::limit(trim((string) $this->body), 80, '…'),
        };
    }
}
