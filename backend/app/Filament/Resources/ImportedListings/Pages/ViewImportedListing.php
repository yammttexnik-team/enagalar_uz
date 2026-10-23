<?php

namespace App\Filament\Resources\ImportedListings\Pages;

use App\Filament\Resources\ImportedListings\ImportedListingResource;
use App\Filament\Support\ImportedListingActions;
use App\Models\ImportedListing;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewImportedListing extends ViewRecord
{
    protected static string $resource = ImportedListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('source')
                ->label('Telegramda ochish')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (ImportedListing $record) => $record->source_url, shouldOpenInNewTab: true),
            ...ImportedListingActions::make(),
        ];
    }
}
