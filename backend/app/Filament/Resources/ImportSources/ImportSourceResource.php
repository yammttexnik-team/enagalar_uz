<?php

namespace App\Filament\Resources\ImportSources;

use App\Console\Commands\FetchImports;
use App\Filament\Resources\ImportSources\Pages\CreateImportSource;
use App\Filament\Resources\ImportSources\Pages\EditImportSource;
use App\Filament\Resources\ImportSources\Pages\ListImportSources;
use App\Filament\Support\AdminOnly;
use App\Models\ImportSource;
use App\Services\Imports\ListingImporter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Throwable;
use UnitEnum;

/** E'lon olinadigan ochiq Telegram kanallar (faqat admin). */
class ImportSourceResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = ImportSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRss;

    protected static string|UnitEnum|null $navigationGroup = 'Sozlamalar';

    protected static ?string $modelLabel = 'Import kanali';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Import kanallari';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('channel')
                ->label('Kanal username')
                ->required()
                ->prefix('t.me/')
                ->maxLength(64)
                ->dehydrateStateUsing(fn (?string $state) => preg_replace('~^(?:https?://)?(?:t\.me/(?:s/)?)?@?~i', '', trim((string) $state)))
                ->rule('regex:~^(?:(?:https?://)?t\.me/(?:s/)?)?@?[A-Za-z0-9_]{4,64}$~i')
                ->unique(ignoreRecord: true)
                ->helperText('Faqat ochiq kanal: masalan enagalar (t.me/s/enagalar ochilishi kerak).'),
            TextInput::make('title')->label('Nomi')->maxLength(255),
            Toggle::make('is_active')->label('Faol')->default(true),
            Textarea::make('notes')->label('Izoh')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->modifyQueryUsing(fn ($query) => $query->withCount('listings'))
            ->columns([
                TextColumn::make('channel')->label('Kanal')->formatStateUsing(fn (string $state) => "@$state")
                    ->url(fn (ImportSource $record) => $record->url(), shouldOpenInNewTab: true)->searchable(),
                TextColumn::make('title')->label('Nomi')->placeholder('—'),
                ToggleColumn::make('is_active')->label('Faol'),
                TextColumn::make('listings_count')->label("E'lonlar")->sortable(),
                TextColumn::make('last_post_id')->label('Oxirgi post')->placeholder('—'),
                TextColumn::make('last_fetched_at')->label('Oxirgi o\'qish')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('fetchNow')
                    ->label('Hozir olish')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription("Kanaldagi yangi postlar o'qiladi va AI orqali e'lonlar ajratiladi (1-2 daqiqa).")
                    ->action(function (ImportSource $record) {
                        try {
                            $stats = app(ListingImporter::class)->importSource($record);
                            Notification::make()->title('@'.$record->channel)->body(FetchImports::summary($stats))->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title('Kanalni o\'qib bo\'lmadi')->body($e->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportSources::route('/'),
            'create' => CreateImportSource::route('/create'),
            'edit' => EditImportSource::route('/{record}/edit'),
        ];
    }
}
