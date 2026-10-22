<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VacancyStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Closed = 'closed';
    case Found = 'found';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Qoralama',
            self::Pending => 'Moderatsiyada',
            self::Active => 'Faol',
            self::Rejected => 'Rad etilgan',
            self::Closed => 'Yopilgan',
            self::Found => 'Topildi',
            self::Expired => "Muddati o'tgan",
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Active => 'success',
            self::Rejected => 'danger',
            self::Closed => 'gray',
            self::Found => 'info',
            self::Expired => 'gray',
        };
    }
}
