<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReportReason: string implements HasLabel
{
    case Spam = 'spam';
    case Fraud = 'fraud';
    case Abuse = 'abuse';
    case Inappropriate = 'inappropriate';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Spam => 'Spam',
            self::Fraud => 'Firibgarlik',
            self::Abuse => 'Haqorat',
            self::Inappropriate => 'Nomaqbul mazmun',
            self::Other => 'Boshqa',
        };
    }
}
