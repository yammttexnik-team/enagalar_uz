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
                TextEntry::make('slug')->label('Kod'),
                TextEntry::make('name_uz')->label("Nomi (o'zbekcha)"),
                TextEntry::make('name_ru')->label('Nomi (ruscha)'),
                TextEntry::make('description_uz')->label("Tavsif (o'zbekcha)")->placeholder('—'),
                TextEntry::make('description_ru')->label('Tavsif (ruscha)')->placeholder('—'),
                TextEntry::make('icon')->label('Ikonka')->placeholder('—'),
                TextEntry::make('color')->label('Rang')
                    ->formatStateUsing(fn ($state) => ['primary' => 'Asosiy', 'success' => 'Yashil', 'info' => "Ko'k", 'warning' => 'Sariq', 'neutral' => 'Kulrang'][$state] ?? $state),
                TextEntry::make('grant_type')->label('Qanday beriladi')
                    ->formatStateUsing(fn ($state) => $state === 'auto' ? 'Tizim avtomatik beradi' : 'Moderator hujjatni tekshirib beradi'),
                TextEntry::make('auto_rule')->label('Avtomatik qoida')->placeholder('—'),
                TextEntry::make('validity_days')->label('Amal qilish muddati (kun)')->numeric()->placeholder('Muddatsiz'),
                IconEntry::make('is_active')->label('Faol')->boolean(),
                TextEntry::make('sort')->label('Tartib')->numeric(),
                TextEntry::make('created_at')->label('Yaratilgan')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('updated_at')->label("O'zgartirilgan")->dateTime('d.m.Y H:i')->placeholder('—'),
            ]);
    }
}
