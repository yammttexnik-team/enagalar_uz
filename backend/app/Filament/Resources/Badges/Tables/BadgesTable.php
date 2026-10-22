<?php

namespace App\Filament\Resources\Badges\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BadgesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name_uz')->label('Nomi')->searchable(),
                TextColumn::make('slug')->label('Kod')->badge()->color('gray'),
                TextColumn::make('grant_type')
                    ->label('Berilishi')
                    ->formatStateUsing(fn (string $state) => $state === 'auto' ? 'Avtomatik' : 'Moderator'),
                TextColumn::make('validity_days')->label('Muddati (kun)')->placeholder('Muddatsiz'),
                TextColumn::make('users_count')->label('Egalari')->counts('users'),
                IconColumn::make('is_active')->label('Faol')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
