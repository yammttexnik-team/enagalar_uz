<?php

namespace App\Filament\Resources\NannyProfiles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class NannyProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('region.id')
                    ->label('Region')
                    ->placeholder('-'),
                TextEntry::make('district.id')
                    ->label('District')
                    ->placeholder('-'),
                TextEntry::make('birth_year')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('bio')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('experience_years')
                    ->numeric(),
                TextEntry::make('price_amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('price_unit')
                    ->badge(),
                TextEntry::make('work_formats')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('languages')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('age_groups')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('live_in')
                    ->boolean(),
                IconEntry::make('free_trial_day')
                    ->boolean(),
                IconEntry::make('is_available')
                    ->boolean(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('rating_avg')
                    ->numeric(),
                TextEntry::make('reviews_count')
                    ->numeric(),
                TextEntry::make('views_count')
                    ->numeric(),
                TextEntry::make('published_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
