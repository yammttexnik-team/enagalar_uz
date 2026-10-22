<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/** Pasport va hujjatlarni tasdiqlash / rad etish tugmalari. */
class ReviewActions
{
    /** @return array<Action> */
    public static function make(): array
    {
        return [
            Action::make('approve')
                ->label('Tasdiqlash')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Foydalanuvchiga nishon beriladi, yuklangan fayl o\'chiriladi.')
                ->visible(fn (Model $record) => $record->isPending())
                ->action(function (Model $record) {
                    $record->approve(auth()->user());
                    Notification::make()->title('Tasdiqlandi, nishon berildi')->success()->send();
                }),
            Action::make('reject')
                ->label('Rad etish')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->schema([
                    Textarea::make('reason')->label('Sabab (foydalanuvchiga ko\'rsatiladi)')->required()->maxLength(255),
                ])
                ->visible(fn (Model $record) => $record->isPending())
                ->action(function (Model $record, array $data) {
                    $record->reject(auth()->user(), $data['reason']);
                    Notification::make()->title('Rad etildi')->warning()->send();
                }),
        ];
    }
}
