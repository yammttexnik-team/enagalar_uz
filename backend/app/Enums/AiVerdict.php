<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** AI moderatsiya xulosasi: faqat maslahat, qaror admin qo'lida. */
enum AiVerdict: string implements HasColor, HasLabel
{
    case Ok = 'ok';
    case Check = 'check';
    case Bad = 'bad';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ok => 'Joyida',
            self::Check => 'Tekshirish',
            self::Bad => 'Shubhali',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ok => 'success',
            self::Check => 'warning',
            self::Bad => 'danger',
        };
    }
}
