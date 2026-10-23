<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Sharhlar moderatsiyasi: ro'yxat va yashirish/ochish (admin va moderator). */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Moderatsiya';

    protected static ?string $modelLabel = 'sharh';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Sharhlar';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['author', 'target']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('author.name')->label('Muallif')->searchable(),
                TextColumn::make('target.name')->label('Kimga')->searchable(),
                TextColumn::make('target_role')->label('Tomon')
                    ->formatStateUsing(fn (string $state) => $state === 'parent' ? 'Ota-ona' : 'Enaga')
                    ->badge(),
                TextColumn::make('rating')->label('Baho')->formatStateUsing(fn (int $state) => str_repeat('★', $state))->sortable(),
                TextColumn::make('text')->label('Sharh')->limit(80)->wrap(),
                TextColumn::make('status')->label('Holat')
                    ->formatStateUsing(fn (string $state) => $state === Review::HIDDEN ? 'Yashirilgan' : 'Ko\'rinadi')
                    ->color(fn (string $state) => $state === Review::HIDDEN ? 'danger' : 'success')
                    ->badge(),
                TextColumn::make('created_at')->label('Yozilgan')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Holat')->options([
                    Review::PUBLISHED => 'Ko\'rinadi',
                    Review::HIDDEN => 'Yashirilgan',
                ]),
            ])
            ->recordActions([
                Action::make('hide')
                    ->label('Yashirish')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record) => $record->status !== Review::HIDDEN)
                    ->action(function (Review $record) {
                        $record->update(['status' => Review::HIDDEN]);
                        Notification::make()->title('Sharh yashirildi')->success()->send();
                    }),
                Action::make('unhide')
                    ->label('Ko\'rsatish')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn (Review $record) => $record->status === Review::HIDDEN)
                    ->action(function (Review $record) {
                        $record->update(['status' => Review::PUBLISHED]);
                        Notification::make()->title('Sharh yana ko\'rinadi')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }
}
