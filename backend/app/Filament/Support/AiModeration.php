<?php

namespace App\Filament\Support;

use App\Enums\AiVerdict;
use App\Models\NannyProfile;
use App\Models\Vacancy;
use App\Services\Ai\ModerationReviewer;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

/** AI moderatsiya oldindan bahosini admin panelda ko'rsatish (faqat maslahat, qaror admin qo'lida). */
class AiModeration
{
    public const FLAG_LABELS = [
        'contact_in_text' => 'Matnda kontakt',
        'offensive' => 'Haqorat',
        'scam_or_prepayment' => 'Firibgarlik / oldindan to\'lov',
        'not_childcare' => 'Bola parvarishi emas',
        'agency_or_mass' => 'Agentlik / ommaviy',
        'too_short_or_empty' => 'Juda qisqa',
        'unrealistic_price' => 'Haqiqiy bo\'lmagan narx',
        'other' => 'Boshqa',
    ];

    public static function column(): TextColumn
    {
        return TextColumn::make('ai_verdict')->label('AI')->badge()
            ->placeholder('—')
            ->tooltip(fn (Vacancy|NannyProfile $record): ?string => self::tooltip($record));
    }

    public static function filter(): SelectFilter
    {
        return SelectFilter::make('ai_verdict')->label('AI xulosasi')->options(AiVerdict::class);
    }

    public static function recheckAction(): Action
    {
        return Action::make('aiRecheck')
            ->label('AI bilan qayta tekshirish')
            ->icon('heroicon-o-sparkles')
            ->color('gray')
            ->visible(fn () => ModerationReviewer::enabled())
            ->action(function (Vacancy|NannyProfile $record) {
                $ok = app(ModerationReviewer::class)->review($record);
                Notification::make()
                    ->title($ok ? 'AI bahosi yangilandi' : 'AI javob bera olmadi')
                    ->color($ok ? 'success' : 'danger')
                    ->send();
            });
    }

    public static function section(): Section
    {
        return Section::make('AI oldindan baho')
            ->description('Faqat maslahat: qarorni moderator qabul qiladi.')
            ->columns(4)
            ->columnSpanFull()
            ->schema([
                TextEntry::make('ai_verdict')->label('Xulosa')->badge()->placeholder('Tekshirilmagan'),
                TextEntry::make('ai_risk')->label('Xavf (0-100)')->placeholder('—'),
                TextEntry::make('ai_flags')->label('Belgilar')->badge()
                    ->formatStateUsing(fn ($state) => self::FLAG_LABELS[$state] ?? $state)
                    ->placeholder('—'),
                TextEntry::make('ai_checked_at')->label('Tekshirilgan')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('ai_summary')->label('Izoh')->placeholder('—')->columnSpanFull(),
            ]);
    }

    private static function tooltip(Vacancy|NannyProfile $record): ?string
    {
        if (! $record->ai_verdict) {
            return null;
        }

        $flags = collect($record->ai_flags ?? [])->map(fn ($f) => self::FLAG_LABELS[$f] ?? $f)->implode(', ');

        return trim($record->ai_summary.($flags !== '' ? " [{$flags}]" : '').' (xavf: '.$record->ai_risk.')');
    }
}
