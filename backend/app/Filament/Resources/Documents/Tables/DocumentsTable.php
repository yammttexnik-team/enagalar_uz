<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Enums\VerificationStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('user.name')->label('Foydalanuvchi')->searchable(),
                TextColumn::make('badge.name_uz')->label('Nishon'),
                TextColumn::make('issued_at')->label('Berilgan sana')->date('d.m.Y')->placeholder('—'),
                TextColumn::make('status')->label('Holat')->badge(),
                TextColumn::make('created_at')->label('Yuborilgan')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('reviewer.name')->label('Tekshirgan')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Holat')->options(VerificationStatus::class)->default(VerificationStatus::Pending->value),
                SelectFilter::make('badge')->label('Nishon')->relationship('badge', 'name_uz'),
            ])
            ->recordActions([
                ViewAction::make()->label('Ko\'rish'),
            ]);
    }
}
