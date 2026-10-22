<?php

namespace App\Filament\Resources\Vacancies;

use App\Filament\Resources\Vacancies\Pages\ListVacancies;
use App\Filament\Resources\Vacancies\Pages\ViewVacancy;
use App\Filament\Resources\Vacancies\Schemas\VacancyInfolist;
use App\Filament\Resources\Vacancies\Tables\VacanciesTable;
use App\Models\Vacancy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class VacancyResource extends Resource
{
    protected static ?string $model = Vacancy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Moderatsiya';

    protected static ?string $modelLabel = 'vakansiya';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Vakansiyalar';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return VacancyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VacanciesTable::configure($table);
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
            'index' => ListVacancies::route('/'),
            'view' => ViewVacancy::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
