<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReportStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Reviewed = 'reviewed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Yangi',
            self::Reviewed => 'Ko\'rib chiqilgan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Reviewed => 'success',
        };
    }
}
