<?php

namespace App\Events;

/** Xabar o'chirildi (hamma uchun). */
class MessageDeleted extends MessageBroadcast
{
    public function broadcastAs(): string
    {
        return 'message.deleted';
    }
}
