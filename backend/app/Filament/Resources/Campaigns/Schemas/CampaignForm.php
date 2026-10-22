<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Models\Campaign;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        $bonus = fn (string $name, string $label, string $config) => TextInput::make($name)
            ->label($label)
            ->numeric()->minValue(0)->suffix("so'm")
            ->placeholder('Standart: '.number_format(config("bonus.$config"), 0, '', ' '))
            ->helperText("Bo'sh = standart miqdor, 0 = bonus yo'q.");

        return $schema->components([
            Section::make('Asosiy')->columns(2)->schema([
                TextInput::make('code')
                    ->label('Kod')
                    ->required()
                    ->maxLength(32)
                    ->regex('/^[A-Za-z0-9_]+$/')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('code', strtoupper((string) $state)))
                    ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state))
                    ->unique(ignoreRecord: true)
                    ->helperText('Faqat A-Z, 0-9 va _ (masalan SURVEY_MOYA). Havolada ishlatiladi.'),
                TextInput::make('name')->label('Nomi')->required()->maxLength(255),
                TextInput::make('source')->label('Kanal / manba izohi')->maxLength(255)->columnSpanFull(),
                Toggle::make('is_active')->label('Faol')->default(true),
            ]),
            Section::make('Bonus miqdorlari')->columns(3)->schema([
                $bonus('survey_bonus', "So'rovnoma bonusi", 'survey'),
                $bonus('listing_bonus', "E'lon / anketa bonusi", 'listing'),
                $bonus('referral_bonus', 'Taklif (referal) bonusi', 'referral'),
            ]),
            Section::make('Muddat va limit')->columns(3)->schema([
                DateTimePicker::make('starts_at')->label('Boshlanishi'),
                DateTimePicker::make('ends_at')->label('Tugashi'),
                TextInput::make('max_participants')->label('Ishtirokchilar limiti')->numeric()->minValue(1)->helperText("Bo'sh = cheksiz."),
            ]),
            Section::make('Havolalar')->columns(2)->visibleOn('edit')->schema([
                Placeholder::make('site_link')->label('Sayt havolasi')->content(fn (?Campaign $record) => $record?->siteLink()),
                Placeholder::make('bot_link')->label('Bot havolasi')->content(fn (?Campaign $record) => $record?->botLink()),
            ]),
        ]);
    }
}
