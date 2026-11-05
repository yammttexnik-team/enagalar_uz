<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Bola qayerda qaraladi: oilaning uyida yoki enaganing o'z uyida (uy bog'chasi). */
enum CareLocation: string implements HasLabel
{
    case FamilyHome = 'family_home';
    case NannyHome = 'nanny_home';

    public function getLabel(): string
    {
        return match ($this) {
            self::FamilyHome => 'Oilaning uyida',
            self::NannyHome => 'Enaganing uyida',
        };
    }
}
