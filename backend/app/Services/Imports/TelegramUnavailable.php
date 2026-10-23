<?php

namespace App\Services\Imports;

use RuntimeException;

/** Telegram vaqtincha javob bermadi (tarmoq, 5xx, noma'lum sahifa). Bu post o'chirilgan degani EMAS. */
class TelegramUnavailable extends RuntimeException {}
