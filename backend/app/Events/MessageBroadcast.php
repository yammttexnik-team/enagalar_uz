<?php

namespace App\Events;

use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Xabar hodisalarining umumiy asosi: `conversation.{id}` shaxsiy kanaliga yuboriladi.
 * Payload — MessageResource (is_mine'siz, ko'ruvchiga bog'liq bo'lmagan ko'rinish).
 * ShouldBroadcastNow: navbat ishchisisiz darhol yuboriladi.
 */
abstract class MessageBroadcast implements ShouldBroadcastNow
{
    public function __construct(public Message $message) {}

    /** @return array<Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->message->conversation_id)];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return MessageResource::neutral($this->message);
    }
}
