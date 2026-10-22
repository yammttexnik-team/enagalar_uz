<?php

namespace App\Filament\Resources\Badges\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BadgeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nomi va tavsifi')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name_uz')->label('Nomi (o\'zbekcha)')->required()->maxLength(100),
                        TextInput::make('name_ru')->label('Nomi (ruscha)')->required()->maxLength(100),
                        TextInput::make('description_uz')->label('Tavsif (o\'zbekcha)')->maxLength(255),
                        TextInput::make('description_ru')->label('Tavsif (ruscha)')->maxLength(255),
                    ]),
                Section::make('Sozlamalar')
                    ->columns(2)
                    ->schema([
                        TextInput::make('slug')
                            ->label('Kod (lotin, chiziqcha bilan)')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit')
                            ->helperText('Masalan: first-aid. Keyin o\'zgartirib bo\'lmaydi.'),
                        TextInput::make('icon')
                            ->label('Ikonka')
                            ->placeholder('i-lucide-badge-check')
                            ->helperText('lucide.dev dagi ikonka nomi, "i-lucide-" bilan.'),
                        Select::make('color')
                            ->label('Rang')
                            ->required()
                            ->default('primary')
                            ->options([
                                'primary' => 'Asosiy', 'success' => 'Yashil', 'info' => 'Ko\'k',
                                'warning' => 'Sariq', 'neutral' => 'Kulrang',
                            ]),
                        Select::make('grant_type')
                            ->label('Qanday beriladi')
                            ->required()
                            ->default('manual')
                            ->options(['manual' => 'Moderator hujjatni tekshirib beradi', 'auto' => 'Tizim avtomatik beradi']),
                        TextInput::make('validity_days')
                            ->label('Amal qilish muddati (kun)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Bo\'sh qoldirilsa, muddatsiz.'),
                        TextInput::make('sort')->label('Tartib')->numeric()->default(0)->required(),
                        Toggle::make('is_active')->label('Faol')->default(true),
                    ]),
            ]);
    }
}
