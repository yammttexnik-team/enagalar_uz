<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/** Foydalanuvchi suhbatni o'qidi: `conversation.{id}` kanaliga. */
class ConversationRead implements ShouldBroadcastNow
{
    public function __construct(
        public int $conversationId,
        public int $userId,
        public int $lastReadMessageId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'conversation.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'last_read_message_id' => $this->lastReadMessageId,
        ];
    }
}
