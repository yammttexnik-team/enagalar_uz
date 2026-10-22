<?php

namespace App\Filament\Resources\NannyProfiles\Pages;

use App\Filament\Resources\NannyProfiles\NannyProfileResource;
use App\Filament\Support\ModerationActions;
use Filament\Resources\Pages\ViewRecord;

class ViewNannyProfile extends ViewRecord
{
    protected static string $resource = NannyProfileResource::class;

    protected function getHeaderActions(): array
    {
        return ModerationActions::forNannyProfile();
    }
}
