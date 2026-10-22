<?php

namespace App\Events;

/** Yangi xabar yuborildi. */
class MessageSent extends MessageBroadcast
{
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
