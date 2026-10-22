<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Conversation;
use App\Models\Report;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reporter.name')->label('Shikoyatchi')->searchable(),
                TextColumn::make('type')
                    ->label('Turi')
                    ->state(fn (Report $record) => $record->reportable_type === (new Conversation)->getMorphClass() ? 'Suhbat' : $record->reportable_type),
                TextColumn::make('reason')->label('Sabab')->badge(),
                TextColumn::make('status')->label('Holat')->badge(),
                TextColumn::make('created_at')->label('Yuborilgan')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Holat')->options(ReportStatus::class)->default(ReportStatus::Open->value),
                SelectFilter::make('reason')->label('Sabab')->options(ReportReason::class),
            ])
            ->recordActions([
                ViewAction::make()->label('Ko\'rish'),
            ]);
    }
}
