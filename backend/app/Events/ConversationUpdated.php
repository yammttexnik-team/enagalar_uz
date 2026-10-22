<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/** Suhbat ro'yxati yangilandi (o'qilmagan soni, oxirgi xabar): `user.{id}` kanaliga. */
class ConversationUpdated implements ShouldBroadcastNow
{
    /** @param  array<string, mixed>  $payload */
    public function __construct(public int $userId, public array $payload) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
