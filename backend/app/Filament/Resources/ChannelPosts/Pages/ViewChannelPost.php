<?php

namespace App\Filament\Resources\ChannelPosts\Pages;

use App\Filament\Resources\ChannelPosts\ChannelPostResource;
use Filament\Resources\Pages\ViewRecord;

class ViewChannelPost extends ViewRecord
{
    protected static string $resource = ChannelPostResource::class;

    protected function getHeaderActions(): array
    {
        return [ChannelPostResource::publishAction()];
    }
}
