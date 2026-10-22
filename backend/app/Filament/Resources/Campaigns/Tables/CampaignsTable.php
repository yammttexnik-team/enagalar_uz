<?php

namespace App\Filament\Resources\Campaigns\Tables;

use App\Enums\SurveyResponseStatus;
use App\Models\Campaign;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount([
                    'users as registrations_count',
                    'surveyResponses as survey_count' => fn ($q) => $q->where('survey_responses.status', SurveyResponseStatus::Completed->value),
                    'bonusTransactions as listings_count' => fn ($q) => $q->where('type', 'listing')->where('amount', '>', 0),
                ])
                ->withSum(['bonusTransactions as bonus_issued' => fn ($q) => $q->where('amount', '>', 0)], 'amount'))
            ->columns([
                TextColumn::make('code')->label('Kod')->badge()->copyable()->searchable()->sortable(),
                TextColumn::make('name')->label('Nomi')->searchable()->description(fn (Campaign $r) => $r->source),
                IconColumn::make('is_active')->label('Faol')->boolean(),
                TextColumn::make('registrations_count')->label("Ro'yxatdan o'tgan")->sortable()
                    ->formatStateUsing(fn ($state, Campaign $r) => $r->max_participants ? "$state / {$r->max_participants}" : $state),
                TextColumn::make('survey_count')->label("So'rovnomalar")->sortable(),
                TextColumn::make('listings_count')->label("E'lonlar")->sortable(),
                TextColumn::make('bonus_issued')->label('Berilgan bonus')->numeric()->suffix(" so'm")->sortable()->placeholder('0'),
                TextColumn::make('ends_at')->label('Tugashi')->dateTime('d.m.Y')->placeholder('—')->toggleable(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Faol')])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
