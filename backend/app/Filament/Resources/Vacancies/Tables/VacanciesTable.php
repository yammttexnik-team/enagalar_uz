<?php

namespace App\Filament\Resources\Vacancies\Tables;

use App\Enums\VacancyStatus;
use App\Filament\Support\AiModeration;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class VacanciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Sarlavha')->searchable()->limit(40),
                TextColumn::make('parentProfile.user.name')->label('Ota-ona'),
                TextColumn::make('region.name_uz')->label('Hudud'),
                TextColumn::make('district.name_uz')->label('Tuman')->placeholder('—'),
                TextColumn::make('work_format')->label('Format')->badge()->color('gray'),
                IconColumn::make('is_urgent')->label('Shoshilinch')->boolean(),
                TextColumn::make('status')->label('Holat')->badge(),
                AiModeration::column(),
                TextColumn::make('expires_at')->label('Tugaydi')->date('d.m.Y')->placeholder('—'),
                TextColumn::make('created_at')->label('Yaratilgan')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Holat')->options(VacancyStatus::class),
                SelectFilter::make('region')->label('Hudud')->relationship('region', 'name_uz'),
                AiModeration::filter(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                AiModeration::recheckAction(),
            ]);
    }
}
