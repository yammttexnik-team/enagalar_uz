<?php

namespace App\Filament\Support;

use App\Enums\ImportedListingStatus;
use App\Models\ImportedListing;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

/** Telegramdan import qilingan e'lonni moderatsiya qilish tugmalari (jadval va ko'rish sahifasi uchun). */
class ImportedListingActions
{
    public static function approve(ImportedListing $listing): void
    {
        $listing->update(['status' => ImportedListingStatus::Active, 'published_at' => $listing->published_at ?? now()]);
    }

    /** @return array<Action> */
    public static function make(): array
    {
        return [
            Action::make('approve')
                ->label('Tasdiqlash')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (ImportedListing $record) => $record->status === ImportedListingStatus::Pending)
                ->action(function (ImportedListing $record) {
                    self::approve($record);
                    Notification::make()->title('Saytga chiqarildi')->success()->send();
                }),
            Action::make('reject')
                ->label('Rad etish')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription("Reklama, enagalikka aloqasi yo'q yoki sifatsiz e'lon bo'lsa.")
                ->visible(fn (ImportedListing $record) => $record->status === ImportedListingStatus::Pending)
                ->action(fn (ImportedListing $record) => $record->update(['status' => ImportedListingStatus::Rejected])),
            Action::make('archive')
                ->label('Arxivlash')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (ImportedListing $record) => $record->status === ImportedListingStatus::Active)
                ->action(fn (ImportedListing $record) => $record->archive('admin')),
            Action::make('restore')
                ->label('Qayta chiqarish')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription("E'lon yana saytda ko'rinadi. Muddati o'tgan bo'lsa, yana ".ImportedListing::LIFETIME_DAYS.' kunga uzaytiriladi.')
                ->visible(fn (ImportedListing $record) => in_array($record->status, [ImportedListingStatus::Archived, ImportedListingStatus::Rejected], true))
                ->action(function (ImportedListing $record) {
                    $record->update([
                        'status' => ImportedListingStatus::Active,
                        'published_at' => $record->published_at ?? now(),
                        'archived_at' => null,
                        'archive_reason' => null,
                        'expires_at' => $record->expires_at?->isFuture() ? $record->expires_at : now()->addDays(ImportedListing::LIFETIME_DAYS),
                    ]);
                }),
        ];
    }

    public static function bulkApprove(): BulkAction
    {
        return BulkAction::make('approveSelected')
            ->label('Tanlanganlarni tasdiqlash')
            ->icon('heroicon-o-check')
            ->color('success')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) {
                $count = 0;
                foreach ($records as $record) {
                    if ($record->status === ImportedListingStatus::Pending) {
                        self::approve($record);
                        $count++;
                    }
                }
                Notification::make()->title("$count ta e'lon saytga chiqarildi")->success()->send();
            });
    }
}
