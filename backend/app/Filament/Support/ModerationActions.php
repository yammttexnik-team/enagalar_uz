<?php

namespace App\Filament\Support;

use App\Enums\NannyProfileStatus;
use App\Enums\VacancyStatus;
use App\Models\NannyProfile;
use App\Models\Vacancy;
use Filament\Actions\Action;

/** Enaga anketasi va vakansiyani moderatsiya qilish tugmalari. */
class ModerationActions
{
    /** @return array<Action> */
    public static function forNannyProfile(): array
    {
        return [
            Action::make('activate')
                ->label('Faollashtirish')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (NannyProfile $record) => $record->status !== NannyProfileStatus::Active)
                ->action(fn (NannyProfile $record) => $record->update([
                    'status' => NannyProfileStatus::Active,
                    'published_at' => $record->published_at ?? now(),
                ])),
            Action::make('reject')
                ->label('Rad etish')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (NannyProfile $record) => $record->status === NannyProfileStatus::Pending)
                ->action(fn (NannyProfile $record) => $record->update(['status' => NannyProfileStatus::Rejected])),
            Action::make('hide')
                ->label('Yashirish')
                ->icon('heroicon-o-eye-slash')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (NannyProfile $record) => $record->status === NannyProfileStatus::Active)
                ->action(fn (NannyProfile $record) => $record->update(['status' => NannyProfileStatus::Hidden])),
        ];
    }

    /** @return array<Action> */
    public static function forVacancy(): array
    {
        return [
            Action::make('publish')
                ->label('E\'lon qilish')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (Vacancy $record) => in_array($record->status, [VacancyStatus::Pending, VacancyStatus::Rejected]))
                ->action(fn (Vacancy $record) => $record->publish()),
            Action::make('reject')
                ->label('Rad etish')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Vacancy $record) => $record->status === VacancyStatus::Pending)
                ->action(fn (Vacancy $record) => $record->update(['status' => VacancyStatus::Rejected])),
        ];
    }
}
