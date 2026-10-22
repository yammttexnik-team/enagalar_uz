<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBot;
use App\Services\Telegram\TelegramLoginHandler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('telegram:poll {--once : Bitta so\'rovdan keyin to\'xtash}')]
#[Description('Lokal ishlab chiqish: webhook o\'rniga botga kelgan xabarlarni long polling orqali qayta ishlaydi')]
class TelegramPoll extends Command
{
    public function handle(TelegramBot $bot, TelegramLoginHandler $handler): int
    {
        if (app()->isProduction()) {
            $this->error('Productionda webhook ishlatiladi (telegram:set-webhook).');

            return self::FAILURE;
        }

        // Webhook o'rnatilgan bo'lsa getUpdates ishlamaydi.
        $bot->deleteWebhook();
        $offset = 0;
        $this->info('Telegram polling boshlandi (Ctrl+C — to\'xtatish).');

        do {
            $updates = $bot->getUpdates($offset)->json('result') ?? [];

            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;
                $handler->handle($update);
                $this->line('update '.$update['update_id'].' qayta ishlandi');
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}
