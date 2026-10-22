<?php

namespace App\Filament\Resources\NannyProfiles\Tables;

use App\Enums\NannyProfileStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NannyProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Enaga')->searchable(),
                TextColumn::make('user.phone')->label('Telefon'),
                TextColumn::make('region.name_uz')->label('Hudud')->placeholder('—'),
                TextColumn::make('district.name_uz')->label('Tuman')->placeholder('—'),
                TextColumn::make('experience_years')->label('Tajriba (yil)')->sortable(),
                TextColumn::make('price_amount')->label('Narx')->numeric(thousandsSeparator: ' ')->suffix(' so\'m')->placeholder('—'),
                IconColumn::make('is_available')->label('Bo\'sh')->boolean(),
                TextColumn::make('rating_avg')->label('Reyting')->sortable(),
                TextColumn::make('status')->label('Holat')->badge(),
                TextColumn::make('created_at')->label('Yaratilgan')->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Holat')->options(NannyProfileStatus::class),
                SelectFilter::make('region')->label('Hudud')->relationship('region', 'name_uz'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
