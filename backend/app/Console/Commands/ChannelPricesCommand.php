<?php

namespace App\Console\Commands;

use App\Models\ChannelPost;
use App\Services\Telegram\ChannelPoster;
use Illuminate\Console\Command;

class ChannelPricesCommand extends Command
{
    protected $signature = 'channel:prices';

    protected $description = 'Kanalga haftalik enaga narxlari postini chiqaradi';

    public function handle(ChannelPoster $poster): int
    {
        $post = $poster->postPrices();

        if ($post->status === ChannelPost::SKIPPED) {
            $this->info("Narx ma'lumoti yetarli emas, o'tkazib yuborildi.");

            return self::SUCCESS;
        }

        $post->status === ChannelPost::SENT ? $this->info('Kanalga yuborildi.') : $this->error('Xato: '.$post->error);

        return $post->status === ChannelPost::SENT ? self::SUCCESS : self::FAILURE;
    }
}
