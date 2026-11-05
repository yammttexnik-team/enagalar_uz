<?php

namespace App\Console\Commands;

use App\Models\ImportedListing;
use App\Services\Imports\ListingTranslator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('imports:translate {--limit=50 : Bir ishga tushishda tarjima qilinadigan e\'lonlar soni}')]
#[Description("Import qilingan e'lonlarning yetishmayotgan o'zbekcha/ruscha tarjimalarini to'ldiradi")]
class TranslateImports extends Command
{
    public function handle(ListingTranslator $translator): int
    {
        if (! config('services.anthropic.enabled') || ! config('services.anthropic.key')) {
            $this->warn('AI o\'chiq: tarjima qilinmadi.');

            return self::SUCCESS;
        }

        $listings = ImportedListing::watched()
            ->where(fn ($q) => $q->whereNull('title_uz')->orWhereNull('description_uz')->orWhereNull('title_ru')->orWhereNull('description_ru'))
            ->latest('id')
            ->limit((int) $this->option('limit'))
            ->get();

        $done = $failed = 0;
        foreach ($listings as $listing) {
            $translator->translate($listing) ? $done++ : $failed++;
        }

        $this->info("Tarjima: $done ta tayyor, $failed ta xato.");

        return self::SUCCESS;
    }
}
