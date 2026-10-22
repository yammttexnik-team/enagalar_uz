<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SurveyResponseStatus: string implements HasColor, HasLabel
{
    case Completed = 'completed';
    case Flagged = 'flagged';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Completed => 'Tugatilgan',
            self::Flagged => 'Tekshiruvda',
            self::Rejected => 'Rad etilgan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::Flagged => 'warning',
            self::Rejected => 'danger',
        };
    }
}
