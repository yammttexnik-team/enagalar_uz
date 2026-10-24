<?php

namespace App\Console\Commands;

use App\Models\ImportSource;
use App\Services\Imports\ListingImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('imports:fetch {--source= : Faqat shu kanal (username)}')]
#[Description("Faol Telegram kanallardan yangi enaga/vakansiya e'lonlarini import qiladi")]
class FetchImports extends Command
{
    public function handle(ListingImporter $importer): int
    {
        $sources = ImportSource::where('is_active', true)
            ->when($this->option('source'), fn ($q, $channel) => $q->where('channel', ltrim($channel, '@')))
            ->orderBy('id')->get();

        foreach ($sources as $source) {
            try {
                $stats = $importer->importSource($source);
                $this->info('@'.$source->channel.': '.self::summary($stats));

                if (($stats[ListingImporter::LIMIT] ?? 0) > 0) {
                    $this->warn('AI chegarasi tugadi (imports.max_ai_per_run), qolganlari keyingi safar.');
                    break;
                }
            } catch (Throwable $e) {
                // Bitta kanal xatosi qolganlarini to'xtatmaydi
                Log::warning('Import: kanalni o\'qib bo\'lmadi', ['channel' => $source->channel, 'error' => $e->getMessage()]);
                $this->error('@'.$source->channel.': '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    /** @param array<string, int> $stats */
    public static function summary(array $stats): string
    {
        $labels = [
            'posts' => 'post', ListingImporter::CREATED => 'yangi', ListingImporter::UPDATED => 'yangilandi',
            ListingImporter::EXISTS => 'avval olingan', ListingImporter::DUPLICATE => 'takror', ListingImporter::FILTERED => 'filtr',
            ListingImporter::OTHER => 'boshqa', ListingImporter::FAILED => 'xato', ListingImporter::LIMIT => 'AI chegarasi', ListingImporter::OPTED_OUT => "egasi o'chirgan",
        ];

        return collect($labels)->filter(fn ($l, $key) => isset($stats[$key]))
            ->map(fn ($l, $key) => "$l: {$stats[$key]}")->implode(', ') ?: 'yangi post yo\'q';
    }
}
