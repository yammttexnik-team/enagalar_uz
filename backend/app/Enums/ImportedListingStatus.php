<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Telegram kanaldan olingan e'lon holati. */
enum ImportedListingStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';   // admin ko'rib chiqishini kutmoqda
    case Active = 'active';     // saytda ko'rinadi
    case Archived = 'archived'; // manbada o'chirilgan / topildi / muddati o'tgan
    case Claimed = 'claimed';   // egasi tasdiqladi — haqiqiy e'longa aylandi
    case Removed = 'removed';   // egasi o'chirishni so'radi
    case Rejected = 'rejected'; // admin rad etdi (e'lon emas, reklama va h.k.)

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Tekshiruvda',
            self::Active => 'Faol',
            self::Archived => 'Arxivda',
            self::Claimed => 'Egasi tasdiqladi',
            self::Removed => "Egasi so'rovi bilan o'chirildi",
            self::Rejected => 'Rad etilgan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'success',
            self::Archived, self::Removed => 'gray',
            self::Claimed => 'info',
            self::Rejected => 'danger',
        };
    }
}
