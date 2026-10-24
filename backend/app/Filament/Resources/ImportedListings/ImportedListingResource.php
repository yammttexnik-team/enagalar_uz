<?php

namespace App\Filament\Resources\ImportedListings;

use App\Enums\ImportedListingStatus;
use App\Filament\Resources\ImportedListings\Pages\ListImportedListings;
use App\Filament\Resources\ImportedListings\Pages\ViewImportedListing;
use App\Filament\Resources\ImportedListings\Schemas\ImportedListingInfolist;
use App\Filament\Resources\ImportedListings\Tables\ImportedListingsTable;
use App\Models\ImportedListing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Telegram kanallardan olingan e'lonlar: moderator tekshiradi va saytga chiqaradi. */
class ImportedListingResource extends Resource
{
    protected static ?string $model = ImportedListing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = 'Moderatsiya';

    protected static ?string $modelLabel = "Import e'lon";

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = "Import e'lonlar";

    protected static ?int $navigationSort = 5;

    public const TYPES = [
        ImportedListing::TYPE_VACANCY => 'Vakansiya',
        ImportedListing::TYPE_NANNY => 'Enaga',
    ];

    public static function getNavigationBadge(): ?string
    {
        // Tekshiruvdagilar + anonim o'chirish so'rovi kelgan faol e'lonlar
        return (string) ImportedListing::where('status', ImportedListingStatus::Pending)
            ->orWhere(fn ($q) => $q->where('status', ImportedListingStatus::Active)->whereNotNull('removal_requested_at'))
            ->count() ?: null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ImportedListingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImportedListingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportedListings::route('/'),
            'view' => ViewImportedListing::route('/{record}'),
        ];
    }
}
