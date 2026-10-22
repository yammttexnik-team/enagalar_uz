<?php

namespace App\Filament\Resources\Vacancies\Schemas;

use App\Models\Vacancy;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VacancyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('parentProfile.id')
                    ->label('Parent profile'),
                TextEntry::make('region.id')
                    ->label('Region'),
                TextEntry::make('district.id')
                    ->label('District')
                    ->placeholder('-'),
                TextEntry::make('title'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('work_format')
                    ->badge(),
                TextEntry::make('schedule')
                    ->placeholder('-'),
                TextEntry::make('salary_min')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('salary_max')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('salary_unit')
                    ->badge(),
                TextEntry::make('children_count')
                    ->numeric(),
                TextEntry::make('children_ages')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('languages')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('live_in')
                    ->boolean(),
                IconEntry::make('is_urgent')
                    ->boolean(),
                TextEntry::make('starts_at')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('published_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('expires_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('views_count')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Vacancy $record): bool => $record->trashed()),
            ]);
    }
}
