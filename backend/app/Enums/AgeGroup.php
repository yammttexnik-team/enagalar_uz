<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Enaga qaysi yoshdagi bolalar bilan ishlaydi. */
enum AgeGroup: string implements HasLabel
{
    case Infant = 'infant';       // 0-1 yosh
    case Toddler = 'toddler';     // 1-3 yosh
    case Preschool = 'preschool'; // 3-6 yosh
    case School = 'school';       // 6+ yosh

    public function getLabel(): string
    {
        return match ($this) {
            self::Infant => '0-1 yosh',
            self::Toddler => '1-3 yosh',
            self::Preschool => '3-6 yosh',
            self::School => '6 yoshdan katta',
        };
    }
}
