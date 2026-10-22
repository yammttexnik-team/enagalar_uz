<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BonusType: string implements HasColor, HasLabel
{
    case Survey = 'survey';
    case Listing = 'listing';
    case Referral = 'referral';
    case Verification = 'verification';
    case Document = 'document';
    case Manual = 'manual';
    case Spend = 'spend';
    case Expire = 'expire';
    case Revoke = 'revoke';

    /** Tavan (lifetime_cap) hisobiga kiradigan, avtomatik topiladigan turlar. */
    public static function earned(): array
    {
        return [self::Survey, self::Listing, self::Referral, self::Verification, self::Document];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Survey => "So'rovnoma",
            self::Listing => "E'lon / anketa",
            self::Referral => 'Taklif (referal)',
            self::Verification => "Pasport tasdig'i",
            self::Document => "Hujjat tasdig'i",
            self::Manual => "Qo'lda",
            self::Spend => 'Sarflangan',
            self::Expire => "Muddati o'tgan",
            self::Revoke => 'Bekor qilingan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Survey, self::Listing, self::Referral, self::Verification, self::Document => 'success',
            self::Manual => 'info',
            self::Spend => 'warning',
            self::Expire, self::Revoke => 'danger',
        };
    }
}
