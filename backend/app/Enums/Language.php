<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Language: string implements HasLabel
{
    case Uz = 'uz';
    case Ru = 'ru';
    case En = 'en';
    case Tg = 'tg';
    case Kk = 'kk';

    public function getLabel(): string
    {
        return match ($this) {
            self::Uz => "O'zbekcha",
            self::Ru => 'Ruscha',
            self::En => 'Inglizcha',
            self::Tg => 'Tojikcha',
            self::Kk => 'Qozoqcha',
        };
    }
}
