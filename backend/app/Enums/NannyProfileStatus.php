<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NannyProfileStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Hidden = 'hidden';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Qoralama',
            self::Pending => 'Moderatsiyada',
            self::Active => 'Faol',
            self::Rejected => 'Rad etilgan',
            self::Hidden => 'Yashirilgan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Active => 'success',
            self::Rejected => 'danger',
            self::Hidden => 'gray',
        };
    }
}
