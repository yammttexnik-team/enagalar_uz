<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Brend Telegram kanaliga chiqariladigan post (kunlik digest). */
#[Fillable(['kind', 'text', 'status', 'items_count', 'sent_at', 'telegram_message_id', 'error'])]
class ChannelPost extends Model
{
    public const DRAFT = 'draft';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const SKIPPED = 'skipped';

    public const STATUSES = [
        self::DRAFT => 'Qoralama',
        self::SENT => 'Yuborilgan',
        self::FAILED => 'Xato',
        self::SKIPPED => "O'tkazib yuborilgan",
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
