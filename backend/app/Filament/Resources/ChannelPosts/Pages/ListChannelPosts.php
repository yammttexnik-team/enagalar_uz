<?php

namespace App\Filament\Resources\ChannelPosts\Pages;

use App\Filament\Resources\ChannelPosts\ChannelPostResource;
use App\Models\ChannelPost;
use App\Services\Telegram\ChannelDigest;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListChannelPosts extends ListRecords
{
    protected static string $resource = ChannelPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('build')
                ->label('Hozir yaratish')
                ->icon('heroicon-o-sparkles')
                ->action(function () {
                    $post = app(ChannelDigest::class)->build();
                    $skipped = $post->status === ChannelPost::SKIPPED;
                    Notification::make()
                        ->title($skipped ? "E'lonlar yetarli emas ({$post->items_count})" : 'Qoralama yaratildi')
                        ->{$skipped ? 'warning' : 'success'}()
                        ->send();
                }),
        ];
    }
}
