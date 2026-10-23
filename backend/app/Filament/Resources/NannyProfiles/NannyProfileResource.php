<?php

namespace App\Filament\Resources\NannyProfiles;

use App\Filament\Resources\NannyProfiles\Pages\ListNannyProfiles;
use App\Filament\Resources\NannyProfiles\Pages\ViewNannyProfile;
use App\Filament\Resources\NannyProfiles\Schemas\NannyProfileInfolist;
use App\Filament\Resources\NannyProfiles\Tables\NannyProfilesTable;
use App\Models\NannyProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NannyProfileResource extends Resource
{
    protected static ?string $model = NannyProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'Foydalanuvchilar';

    protected static ?string $modelLabel = 'Enaga anketasi';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Enaga anketalari';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return NannyProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NannyProfilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNannyProfiles::route('/'),
            'view' => ViewNannyProfile::route('/{record}'),
        ];
    }
}
