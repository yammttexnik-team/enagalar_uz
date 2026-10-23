<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WorkFormat: string implements HasLabel
{
    case Hourly = 'hourly';
    case Daily = 'daily';
    case FullTime = 'full_time';
    case LiveIn = 'live_in';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hourly => 'Soatbay',
            self::Daily => 'Kunlik',
            self::FullTime => "To'liq kun",
            self::LiveIn => 'Yashab turish bilan',
        };
    }
}
