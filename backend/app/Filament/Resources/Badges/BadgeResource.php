<?php

namespace App\Filament\Resources\Badges;

use App\Filament\Resources\Badges\Pages\CreateBadge;
use App\Filament\Resources\Badges\Pages\EditBadge;
use App\Filament\Resources\Badges\Pages\ListBadges;
use App\Filament\Resources\Badges\Pages\ViewBadge;
use App\Filament\Resources\Badges\Schemas\BadgeForm;
use App\Filament\Resources\Badges\Schemas\BadgeInfolist;
use App\Filament\Resources\Badges\Tables\BadgesTable;
use App\Filament\Support\AdminOnly;
use App\Models\Badge;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BadgeResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Badge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Sozlamalar';

    protected static ?string $modelLabel = 'nishon';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Nishonlar';

    public static function form(Schema $schema): Schema
    {
        return BadgeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BadgeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BadgesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBadges::route('/'),
            'create' => CreateBadge::route('/create'),
            'view' => ViewBadge::route('/{record}'),
            'edit' => EditBadge::route('/{record}/edit'),
        ];
    }
}
