<?php

namespace App\Console\Commands;

use App\Services\Rating\ConnectionService;
use App\Services\Telegram\TelegramBot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('connections:ask')]
#[Description('Bog\'langaniga 3 kun bo\'lgan juftliklardan "Birga ishladingizmi?" deb so\'raydi (bir marta)')]
class AskConnections extends Command
{
    public function handle(ConnectionService $connections, TelegramBot $bot): int
    {
        $this->info($connections->askDue($bot).' ta bog\'lanish bo\'yicha so\'raldi.');

        return self::SUCCESS;
    }
}
