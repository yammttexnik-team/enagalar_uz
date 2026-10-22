<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Ism')->searchable(),
                TextColumn::make('phone')->label('Telefon')->searchable(),
                TextColumn::make('telegram_username')->label('Telegram')->prefix('@')->placeholder('—'),
                TextColumn::make('roles.name')->label('Rollar')->badge(),
                TextColumn::make('badges.name_uz')->label('Nishonlar')->badge()->color('success')->limitList(2),
                TextColumn::make('campaign.code')->label('Kampaniya')->badge()->color('gray')->placeholder('—')->toggleable(),
                TextColumn::make('status')->label('Holat')->badge(),
                TextColumn::make('created_at')->label('Ro\'yxatdan o\'tgan')->dateTime('d.m.Y')->sortable(),
                TextColumn::make('last_seen_at')->label('Oxirgi faollik')->since()->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('roles')->label('Rol')->relationship('roles', 'name'),
                SelectFilter::make('status')->label('Holat')->options(UserStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
