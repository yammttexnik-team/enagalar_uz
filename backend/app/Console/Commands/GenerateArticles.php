<?php

namespace App\Console\Commands;

use App\Services\Content\ArticleTopics;
use App\Services\Content\ArticleWriter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('articles:generate {--count=1 : Nechta yangi maqola}')]
#[Description("Rejadagi keyingi mavzular bo'yicha SEO maqolalarini Claude bilan yozib e'lon qiladi")]
class GenerateArticles extends Command
{
    public function handle(ArticleTopics $topics, ArticleWriter $writer): int
    {
        if (! config('services.anthropic.enabled') || ! config('services.anthropic.key')) {
            $this->warn('AI o\'chiq yoki kalit yo\'q.');

            return self::SUCCESS;
        }

        $made = 0;
        $failed = false;
        for ($i = 0; $i < max(1, (int) $this->option('count')); $i++) {
            $key = $topics->next();
            if ($key === null) {
                $this->info('Barcha mavzular tayyor.');
                break;
            }

            try {
                $article = $writer->write($key);
            } catch (Throwable $e) {
                report($e);
                $this->error("{$key}: ".$e->getMessage());
                $failed = true;
                break;
            }

            $this->info("{$key} -> /maqolalar/{$article->slug}");
            $made++;
        }

        $this->info("{$made} ta maqola e'lon qilindi.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
