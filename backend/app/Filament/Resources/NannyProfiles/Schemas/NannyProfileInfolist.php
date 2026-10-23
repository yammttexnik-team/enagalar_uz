<?php

namespace App\Filament\Resources\NannyProfiles\Schemas;

use App\Enums\AgeGroup;
use App\Enums\Language;
use App\Enums\WorkFormat;
use App\Filament\Support\AiModeration;
use App\Filament\Support\EnumLabel;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class NannyProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                AiModeration::section(),
                TextEntry::make('user.name')->label('Enaga'),
                TextEntry::make('region.name_uz')->label('Viloyat')->placeholder('—'),
                TextEntry::make('district.name_uz')->label('Tuman')->placeholder('—'),
                TextEntry::make('birth_year')->label("Tug'ilgan yili")->placeholder('—'),
                TextEntry::make('bio')->label("O'zi haqida")->placeholder('—')->columnSpanFull(),
                TextEntry::make('experience_years')->label('Tajriba (yil)')->numeric(),
                TextEntry::make('price_amount')->label('Narx')->numeric(thousandsSeparator: ' ')->suffix(" so'm")->placeholder('—'),
                TextEntry::make('price_unit')->label('Narx davri')->badge(),
                TextEntry::make('work_formats')->label('Ish formatlari')
                    ->badge()->formatStateUsing(EnumLabel::using(WorkFormat::class))
                    ->placeholder('—')->columnSpanFull(),
                TextEntry::make('languages')->label('Tillar')
                    ->badge()->formatStateUsing(EnumLabel::using(Language::class))
                    ->placeholder('—')->columnSpanFull(),
                TextEntry::make('age_groups')->label('Bolalar yoshi')
                    ->badge()->formatStateUsing(EnumLabel::using(AgeGroup::class))
                    ->placeholder('—')->columnSpanFull(),
                IconEntry::make('live_in')->label('Yashab turish bilan')->boolean(),
                IconEntry::make('free_trial_day')->label('Bepul sinov kuni')->boolean(),
                IconEntry::make('is_available')->label("Hozir bo'sh")->boolean(),
                TextEntry::make('status')->label('Holat')->badge(),
                TextEntry::make('rating_avg')->label('Reyting')->numeric(),
                TextEntry::make('reviews_count')->label('Sharhlar soni')->numeric(),
                TextEntry::make('views_count')->label("Ko'rishlar soni")->numeric(),
                TextEntry::make('published_at')->label("E'lon qilingan")->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('created_at')->label('Yaratilgan')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('updated_at')->label("O'zgartirilgan")->dateTime('d.m.Y H:i')->placeholder('—'),
            ]);
    }
}
