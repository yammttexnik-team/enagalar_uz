<?php

namespace App\Console\Commands;

use App\Models\IdentityVerification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('verification:prune {--days=30 : Ko\'rib chiqilganidan keyin necha kun saqlanadi}')]
#[Description("Ko'rib chiqilgan tasdiqlash so'rovlaridan pasport raqami va PINFL ni o'chiradi")]
class PruneVerifications extends Command
{
    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));

        // Query builder: shifrlangan qiymatni ochmasdan to'g'ridan-to'g'ri NULL qilamiz.
        $count = IdentityVerification::query()
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '<', now()->subDays($days))
            ->where(fn ($q) => $q->whereNotNull('passport_number')->orWhereNotNull('pinfl'))
            ->update(['passport_number' => null, 'pinfl' => null]);

        $this->info("{$count} ta so'rovdan pasport raqami va PINFL tozalandi.");

        return self::SUCCESS;
    }
}
