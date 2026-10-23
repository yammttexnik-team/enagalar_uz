<?php

namespace App\Filament\Resources\ChannelPosts;

use App\Filament\Resources\ChannelPosts\Pages\ListChannelPosts;
use App\Filament\Resources\ChannelPosts\Pages\ViewChannelPost;
use App\Models\ChannelPost;
use App\Services\Telegram\ChannelDigest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Brend kanaliga chiqariladigan postlar (kunlik digest). */
class ChannelPostResource extends Resource
{
    protected static ?string $model = ChannelPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'kanal posti';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Kanal postlari';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    /** Qoralamani kanalga chiqarish (jadval va ko'rish sahifasida bir xil). */
    public static function publishAction(): Action
    {
        return Action::make('publish')
            ->label('Kanalga chiqarish')
            ->icon('heroicon-o-paper-airplane')
            ->requiresConfirmation()
            ->visible(fn (ChannelPost $record) => in_array($record->status, [ChannelPost::DRAFT, ChannelPost::FAILED], true))
            ->action(function (ChannelPost $record) {
                if (app(ChannelDigest::class)->publish($record)) {
                    Notification::make()->title('Kanalga chiqarildi')->success()->send();
                } else {
                    Notification::make()->title('Xato')->body((string) $record->error)->danger()->send();
                }
            });
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('status')->label('Holat')->badge()
                ->formatStateUsing(fn ($state) => ChannelPost::STATUSES[$state] ?? $state),
            TextEntry::make('items_count')->label("E'lonlar soni"),
            TextEntry::make('sent_at')->label('Yuborilgan')->dateTime('d.m.Y H:i')->placeholder('—'),
            TextEntry::make('error')->label('Xato')->placeholder('—')->columnSpanFull(),
            TextEntry::make('text')->label('Matn')->html()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('status')->label('Holat')->badge()
                    ->formatStateUsing(fn ($state) => ChannelPost::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        ChannelPost::SENT => 'success',
                        ChannelPost::DRAFT => 'warning',
                        ChannelPost::FAILED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('items_count')->label("E'lonlar")->sortable(),
                TextColumn::make('created_at')->label('Yaratilgan')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('sent_at')->label('Yuborilgan')->dateTime('d.m.Y H:i')->placeholder('—')->sortable(),
            ])
            ->recordActions([ViewAction::make(), self::publishAction()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChannelPosts::route('/'),
            'view' => ViewChannelPost::route('/{record}'),
        ];
    }
}
