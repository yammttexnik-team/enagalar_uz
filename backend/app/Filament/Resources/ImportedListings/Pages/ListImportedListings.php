<?php

namespace App\Filament\Resources\ImportedListings\Pages;

use App\Filament\Resources\ImportedListings\ImportedListingResource;
use Filament\Resources\Pages\ListRecords;

class ListImportedListings extends ListRecords
{
    protected static string $resource = ImportedListingResource::class;
}
