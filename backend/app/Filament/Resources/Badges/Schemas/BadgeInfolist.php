<?php

namespace App\Filament\Resources\Badges\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BadgeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('slug'),
                TextEntry::make('name_uz'),
                TextEntry::make('name_ru'),
                TextEntry::make('description_uz')
                    ->placeholder('-'),
                TextEntry::make('description_ru')
                    ->placeholder('-'),
                TextEntry::make('icon')
                    ->placeholder('-'),
                TextEntry::make('color'),
                TextEntry::make('grant_type'),
                TextEntry::make('auto_rule')
                    ->placeholder('-'),
                TextEntry::make('validity_days')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('sort')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
