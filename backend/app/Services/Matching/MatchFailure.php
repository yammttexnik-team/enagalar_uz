<?php

namespace App\Services\Matching;

use RuntimeException;

/** AI javobi yaroqsiz (rad, kesilgan, bo'sh) — qoidalarga qaytish kerak. */
class MatchFailure extends RuntimeException
{
    public function __construct(string $message, public readonly string $status)
    {
        parent::__construct($message);
    }
}
