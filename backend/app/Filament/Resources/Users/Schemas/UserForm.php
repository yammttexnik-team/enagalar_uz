<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Filament\Support\EnumLabel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Ism')->required()->maxLength(255),
                Select::make('status')->label('Holat')->options(UserStatus::class)->required(),
                Select::make('roles')
                    ->label('Rollar')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => EnumLabel::of(Role::class, $record->name))
                    ->multiple()
                    ->preload(),
                Select::make('badges')
                    ->label('Nishonlar')
                    ->relationship('badges', 'name_uz')
                    ->multiple()
                    ->preload()
                    ->pivotData(fn () => ['granted_at' => now(), 'granted_by' => auth()->id()])
                    ->helperText('Faqat o\'zingiz tekshirgan ma\'lumot uchun bering. Foydalanuvchiga "profilda ko\'rsatilsinmi?" so\'rovi boradi, rozi bo\'lsa nishon profilda chiqadi.'),
            ]);
    }
}
