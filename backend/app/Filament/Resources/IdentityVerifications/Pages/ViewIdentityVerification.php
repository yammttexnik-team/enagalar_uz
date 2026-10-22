<?php

namespace App\Filament\Resources\IdentityVerifications\Pages;

use App\Filament\Resources\IdentityVerifications\IdentityVerificationResource;
use App\Filament\Support\ReviewActions;
use Filament\Resources\Pages\ViewRecord;

class ViewIdentityVerification extends ViewRecord
{
    protected static string $resource = IdentityVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return ReviewActions::make();
    }
}
