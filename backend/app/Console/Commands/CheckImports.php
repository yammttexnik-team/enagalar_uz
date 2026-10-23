<?php

namespace App\Console\Commands;

use App\Models\ImportedListing;
use App\Services\Imports\TelegramChannelReader;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('imports:check {--limit=500 : Bir ishga tushishda tekshiriladigan e\'lonlar soni}')]
#[Description("Import qilingan e'lonlarni manbada tekshiradi: o'chirilgan, \"topildi\" yoki muddati o'tganlarini arxivlaydi")]
class CheckImports extends Command
{
    /** Post tahrirlanib shu so'zlar qo'shilsa — enaga topilgan / e'lon yopilgan. */
    private const FOUND_MARKERS = '~\b(?:topildi|topdik|topildik|olindi|band|ishga oldik|yopildi|aktual emas|актуально эмас)\b|найден|нашли|закрыт|неактуальн|не актуальн~u';

    public function handle(TelegramChannelReader $reader): int
    {
        $expired = 0;
        ImportedListing::watched()->whereNotNull('expires_at')->where('expires_at', '<=', now())
            ->each(function (ImportedListing $listing) use (&$expired) {
                $listing->archive('expired');
                $expired++;
            });

        $stats = ['deleted' => 0, 'found' => 0, 'ok' => 0, 'error' => 0];

        $listings = ImportedListing::watched()
            ->orderByRaw('last_checked_at is not null')->orderBy('last_checked_at')
            ->limit((int) $this->option('limit'))->get();

        foreach ($listings as $listing) {
            try {
                $text = $reader->fetchPost($listing->source_channel, (int) $listing->source_post_id);
            } catch (Throwable $e) {
                // Tarmoq xatosi / 5xx — hech narsa o'zgarmaydi, keyingi safar qayta tekshiriladi
                $stats['error']++;
                Log::info('Import tekshiruvi: post ochilmadi', ['id' => $listing->id, 'error' => $e->getMessage()]);

                continue;
            }

            if ($text === null) {
                $listing->archive('deleted');
                $stats['deleted']++;
            } elseif (self::newMarkers($listing->raw_text, $text)) {
                $listing->archive('found');
                $stats['found']++;
            } else {
                $listing->update(['last_checked_at' => now()]);
                $stats['ok']++;
            }
        }

        $this->info("Muddati o'tgan: $expired, o'chirilgan: {$stats['deleted']}, topilgan: {$stats['found']}, "
            ."joyida: {$stats['ok']}, ochilmadi: {$stats['error']}");

        return self::SUCCESS;
    }

    /** Yangi matnda asl matnda bo'lmagan "topildi/закрыта" belgisi bormi. */
    public static function newMarkers(string $original, string $current): bool
    {
        preg_match_all(self::FOUND_MARKERS, mb_strtolower($current), $now);
        preg_match_all(self::FOUND_MARKERS, mb_strtolower($original), $before);

        return array_diff($now[0], $before[0]) !== [];
    }
}
