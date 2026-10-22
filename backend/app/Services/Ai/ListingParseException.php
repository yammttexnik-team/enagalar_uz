<?php

namespace App\Services\Ai;

use RuntimeException;

/** "Matnni joylash" xatosi: limit tugagan (429) yoki AI ishlamayapti (503). */
class ListingParseException extends RuntimeException
{
    public function __construct(string $message, public readonly int $httpStatus, public readonly string $reason)
    {
        parent::__construct($message);
    }

    public static function limit(): self
    {
        return new self('Bugungi AI so\'rovlar limiti tugadi. Ertaga qayta urinib ko\'ring yoki formani qo\'lda to\'ldiring.', 429, 'limit');
    }

    public static function unavailable(): self
    {
        return new self('AI xizmati hozir ishlamayapti. Formani qo\'lda to\'ldiring yoki keyinroq urinib ko\'ring.', 503, 'unavailable');
    }
}
