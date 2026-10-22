<?php

namespace App\Events;

/** Xabar tahrirlandi. */
class MessageUpdated extends MessageBroadcast
{
    public function broadcastAs(): string
    {
        return 'message.updated';
    }
}
