<?php

namespace App\Filament\Resources\Vacancies\Schemas;

use App\Enums\Language;
use App\Filament\Support\AiModeration;
use App\Filament\Support\EnumLabel;
use App\Models\Vacancy;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VacancyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                AiModeration::section(),
                TextEntry::make('title')->label('Sarlavha'),
                TextEntry::make('parentProfile.user.name')->label('Ota-ona'),
                TextEntry::make('region.name_uz')->label('Viloyat'),
                TextEntry::make('district.name_uz')->label('Tuman')->placeholder('—'),
                TextEntry::make('description')->label('Tavsif')->columnSpanFull(),
                TextEntry::make('work_format')->label('Ish formati')->badge(),
                TextEntry::make('schedule')->label('Ish jadvali')->placeholder('—'),
                TextEntry::make('salary_min')->label('Maosh (dan)')->numeric(thousandsSeparator: ' ')->suffix(" so'm")->placeholder('—'),
                TextEntry::make('salary_max')->label('Maosh (gacha)')->numeric(thousandsSeparator: ' ')->suffix(" so'm")->placeholder('—'),
                TextEntry::make('salary_unit')->label('Maosh davri')->badge(),
                TextEntry::make('children_count')->label('Bolalar soni')->numeric(),
                TextEntry::make('children_ages')->label('Bolalar yoshi')
                    ->badge()->formatStateUsing(fn ($state) => $state.' yosh')
                    ->placeholder('—')->columnSpanFull(),
                TextEntry::make('languages')->label('Tillar')
                    ->badge()->formatStateUsing(EnumLabel::using(Language::class))
                    ->placeholder('—')->columnSpanFull(),
                IconEntry::make('live_in')->label('Yashab turish bilan')->boolean(),
                IconEntry::make('is_urgent')->label('Shoshilinch')->boolean(),
                TextEntry::make('starts_at')->label('Boshlanish sanasi')->date('d.m.Y')->placeholder('—'),
                TextEntry::make('status')->label('Holat')->badge(),
                TextEntry::make('published_at')->label("E'lon qilingan")->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('expires_at')->label('Tugaydi')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('views_count')->label("Ko'rishlar soni")->numeric(),
                TextEntry::make('created_at')->label('Yaratilgan')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('updated_at')->label("O'zgartirilgan")->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('deleted_at')->label("O'chirilgan")->dateTime('d.m.Y H:i')
                    ->visible(fn (Vacancy $record): bool => $record->trashed()),
            ]);
    }
}
