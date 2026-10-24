<?php

namespace App\Filament\Resources\ImportedListings\Tables;

use App\Enums\ImportedListingStatus;
use App\Filament\Resources\ImportedListings\ImportedListingResource;
use App\Filament\Support\ImportedListingActions;
use App\Models\ImportedListing;
use App\Models\ImportSource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImportedListingsTable
{
    private const AI_LABELS = ['approve' => 'Tasdiqlagan', 'reject' => 'Rad etgan', 'manual' => "Qo'lda ko'rish"];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('source_posted_at', 'desc')
            ->columns([
                TextColumn::make('type')->label('Turi')->badge()
                    ->formatStateUsing(fn (string $state) => ImportedListingResource::TYPES[$state] ?? $state)
                    ->color(fn (string $state) => $state === ImportedListing::TYPE_VACANCY ? 'info' : 'primary'),
                TextColumn::make('title')->label('Sarlavha')->searchable()->limit(45)
                    ->tooltip(fn (ImportedListing $record) => $record->title),
                TextColumn::make('region.name_uz')->label('Hudud')->placeholder('—'),
                TextColumn::make('district.name_uz')->label('Tuman')->placeholder('—')->toggleable(),
                TextColumn::make('status')->label('Holat')->badge()
                    ->description(fn (ImportedListing $record) => $record->removal_requested_at && $record->status === ImportedListingStatus::Active ? "O'chirish so'ralgan" : null),
                TextColumn::make('ai_decision')->label('AI')->badge()->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => self::AI_LABELS[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'approve' => 'success',
                        'reject' => 'danger',
                        'manual' => 'warning',
                        default => 'gray',
                    })
                    ->tooltip(fn (ImportedListing $record) => $record->ai_reason),
                TextColumn::make('source_channel')->label('Manba')
                    ->formatStateUsing(fn (string $state, ImportedListing $record) => "@$state/{$record->source_post_id}")
                    ->url(fn (ImportedListing $record) => $record->source_url, shouldOpenInNewTab: true)
                    ->color('primary'),
                TextColumn::make('source_posted_at')->label('Joylangan')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('expires_at')->label('Tugaydi')->date('d.m.Y')->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Holat')->options(ImportedListingStatus::class)
                    ->default(ImportedListingStatus::Pending->value),
                Filter::make('removal_requested')->label("O'chirish so'rovi bor")
                    ->query(fn (Builder $q) => $q->whereNotNull('removal_requested_at')->where('status', ImportedListingStatus::Active)),
                SelectFilter::make('ai_decision')->label('AI qarori')->options(self::AI_LABELS),
                SelectFilter::make('type')->label('Turi')->options(ImportedListingResource::TYPES),
                SelectFilter::make('source_channel')->label('Kanal')
                    ->options(fn () => ImportSource::orderBy('channel')->pluck('channel', 'channel')->map(fn ($c) => "@$c")->all()),
            ])
            ->recordActions([
                ViewAction::make()->label('Ko\'rish'),
                ...ImportedListingActions::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([ImportedListingActions::bulkApprove()]),
            ]);
    }
}
