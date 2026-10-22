<?php

namespace App\Console\Commands;

use App\Services\Bonus\BonusService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bonus:expire')]
#[Description("Muddati o'tgan bonuslarni daftarda kuydiradi (expire yozuvlari)")]
class ExpireBonuses extends Command
{
    public function handle(BonusService $bonuses): int
    {
        $this->info($bonuses->expireAll()." ta expire yozuvi yozildi.");

        return self::SUCCESS;
    }
}
