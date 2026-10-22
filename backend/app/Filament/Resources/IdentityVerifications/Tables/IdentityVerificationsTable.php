<?php

namespace App\Filament\Resources\IdentityVerifications\Tables;

use App\Enums\VerificationStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IdentityVerificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('user.name')->label('Foydalanuvchi')->searchable(),
                TextColumn::make('user.phone')->label('Telefon'),
                TextColumn::make('status')->label('Holat')->badge(),
                TextColumn::make('created_at')->label('Yuborilgan')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('reviewer.name')->label('Tekshirgan')->placeholder('—'),
                TextColumn::make('reviewed_at')->label('Tekshirilgan')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Holat')->options(VerificationStatus::class)->default(VerificationStatus::Pending->value),
            ])
            ->recordActions([
                ViewAction::make()->label('Ko\'rish'),
            ]);
    }
}
