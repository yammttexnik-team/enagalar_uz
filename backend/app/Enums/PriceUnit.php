<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PriceUnit: string implements HasLabel
{
    case Hour = 'hour';
    case Day = 'day';
    case Month = 'month';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hour => 'Soatiga',
            self::Day => 'Kuniga',
            self::Month => 'Oyiga',
        };
    }
}
