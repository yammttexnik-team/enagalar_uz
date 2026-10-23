<?php

namespace App\Console\Commands;

use App\Models\ChannelPost;
use App\Services\Telegram\ChannelDigest;
use Illuminate\Console\Command;

class ChannelDigestCommand extends Command
{
    protected $signature = 'channel:digest {--send : Hozir kanalga yuborish}';

    protected $description = "Kunlik e'lonlar digestini yaratadi (kanalga yuboradi yoki adminga qoralama jo'natadi)";

    public function handle(ChannelDigest $digest): int
    {
        $post = $digest->build();

        if ($post->status === ChannelPost::SKIPPED) {
            $this->info("E'lonlar yetarli emas ({$post->items_count}), o'tkazib yuborildi.");

            return self::SUCCESS;
        }

        if ($this->option('send') || config('services.telegram.digest_auto')) {
            $ok = $digest->publish($post);
            $ok ? $this->info('Kanalga yuborildi.') : $this->error('Xato: '.$post->error);

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        $digest->notifyAdmin($post);
        $this->info('Qoralama saqlandi, adminga yuborildi.');

        return self::SUCCESS;
    }
}
