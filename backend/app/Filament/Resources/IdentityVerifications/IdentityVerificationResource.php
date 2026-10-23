<?php

namespace App\Filament\Resources\IdentityVerifications;

use App\Enums\VerificationStatus;
use App\Filament\Resources\IdentityVerifications\Pages\ListIdentityVerifications;
use App\Filament\Resources\IdentityVerifications\Pages\ViewIdentityVerification;
use App\Filament\Resources\IdentityVerifications\Schemas\IdentityVerificationInfolist;
use App\Filament\Resources\IdentityVerifications\Tables\IdentityVerificationsTable;
use App\Models\IdentityVerification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IdentityVerificationResource extends Resource
{
    protected static ?string $model = IdentityVerification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Moderatsiya';

    protected static ?string $modelLabel = 'Pasport tekshiruvi';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Pasport tekshiruvlari';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) IdentityVerification::where('status', VerificationStatus::Pending)->count() ?: null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return IdentityVerificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IdentityVerificationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIdentityVerifications::route('/'),
            'view' => ViewIdentityVerification::route('/{record}'),
        ];
    }
}
