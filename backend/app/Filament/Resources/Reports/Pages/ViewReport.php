<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Enums\ReportStatus;
use App\Enums\UserStatus;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    /** Moderator yozishmani ochganini qayd qiladi (maxfiy ma'lumotga kirish izi). */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->getRecord()->conversation()) {
            Log::info('Moderator shikoyat qilingan yozishmani ochdi', [
                'report_id' => $this->getRecord()->id,
                'conversation_id' => $this->getRecord()->reportable_id,
                'moderator_id' => auth()->id(),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reviewed')
                ->label('Ko\'rib chiqildi')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (Report $record) => $record->status === ReportStatus::Open)
                ->action(function (Report $record) {
                    $record->update(['status' => ReportStatus::Reviewed, 'handled_by' => auth()->id()]);
                    Notification::make()->title('Ko\'rib chiqildi deb belgilandi')->success()->send();
                }),
            Action::make('block_user')
                ->label('Foydalanuvchini bloklash')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Shikoyat qilingan foydalanuvchi bloklanadi va tizimdan chiqariladi.')
                ->visible(fn (Report $record) => $record->reportedUser()?->status === UserStatus::Active)
                ->action(function (Report $record) {
                    $user = $record->reportedUser();
                    $user->update(['status' => UserStatus::Blocked]);
                    $user->tokens()->delete();
                    $record->update(['status' => ReportStatus::Reviewed, 'handled_by' => auth()->id()]);
                    Log::notice('Moderator foydalanuvchini blokladi', ['user_id' => $user->id, 'report_id' => $record->id, 'moderator_id' => auth()->id()]);
                    Notification::make()->title('Foydalanuvchi bloklandi')->warning()->send();
                }),
        ];
    }
}
