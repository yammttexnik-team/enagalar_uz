<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    case Parent = 'parent';
    case Nanny = 'nanny';
    case Moderator = 'moderator';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Parent => 'Ota-ona',
            self::Nanny => 'Enaga',
            self::Moderator => 'Moderator',
            self::Admin => 'Admin',
        };
    }
}
