<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Models\Report;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Shikoyat')
                ->columns(2)
                ->schema([
                    TextEntry::make('reporter.name')->label('Shikoyatchi'),
                    TextEntry::make('reported')
                        ->label('Shikoyat qilingan')
                        ->state(fn (Report $record) => $record->reportedUser()?->name)
                        ->placeholder('—'),
                    TextEntry::make('reason')->label('Sabab')->badge(),
                    TextEntry::make('status')->label('Holat')->badge(),
                    TextEntry::make('comment')->label('Izoh')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('created_at')->label('Yuborilgan')->dateTime('d.m.Y H:i'),
                    TextEntry::make('handler.name')->label('Ko\'rib chiqqan')->placeholder('—'),
                ]),
            Section::make('Yozishma (oxirgi 100 xabar, faqat o\'qish)')
                ->description('Bu sahifani ochishingiz qayd qilinadi.')
                ->visible(fn (Report $record) => (bool) $record->conversation())
                ->schema([
                    ViewEntry::make('messages')
                        ->hiddenLabel()
                        ->view('filament.reports.conversation-messages')
                        ->state(fn (Report $record) => $record->conversation()
                            ?->messages()->withTrashed()->with('sender')->latest('id')->limit(100)->get()->reverse()->values()),
                ]),
        ]);
    }
}
