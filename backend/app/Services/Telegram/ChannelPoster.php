<?php

namespace App\Services\Telegram;

use App\Models\Article;
use App\Models\ChannelPost;
use App\Services\PriceStats;

/** Brend kanaliga avtomatik postlar: yangi maqola va haftalik narxlar. */
class ChannelPoster
{
    public function __construct(private ChannelDigest $digest, private PriceStats $prices) {}

    public function postArticle(Article $article): ChannelPost
    {
        $url = rtrim((string) config('app.frontend_url'), '/').'/maqolalar/'.$article->slug;
        $text = '<b>'.e($article->title_uz)."</b>\n\n".e($article->excerpt_uz)."\n\n".'<a href="'.e($url).'">Oʻqish</a>';

        return $this->publish('article', $text, 1);
    }

    /** Yetarli ma'lumot bo'lmasa `skipped` post qaytaradi. */
    public function postPrices(): ChannelPost
    {
        $city = $this->prices->compute('tashkent-city')['units']['month'] ?? [];
        $all = $this->prices->compute()['units']['month'] ?? [];
        $overall = $all['vacancy'] ?? $all['nanny'] ?? null;

        if (! $overall) {
            return ChannelPost::create(['kind' => 'prices', 'text' => '', 'status' => ChannelPost::SKIPPED]);
        }

        $fmt = fn (int $n) => number_format($n, 0, '', ' ');
        $line = fn (string $label, array $b) => "$label: oylik median <b>".$fmt($b['median']).' soʻm</b> (odatda '.$fmt($b['p25']).'–'.$fmt($b['p75']).' soʻm)';

        $lines = [];
        if ($cityBucket = $city['vacancy'] ?? $city['nanny'] ?? null) {
            $lines[] = $line('Toshkent shahri', $cityBucket);
        }
        $lines[] = $line('Oʻzbekiston boʻyicha', $overall);

        $url = rtrim((string) config('app.frontend_url'), '/').'/narxlar';
        $text = "<b>Bu hafta enaga narxlari</b>\n\n".implode("\n", $lines)
            ."\n\nNarxlar enagalar.uz va ochiq manbalardagi eʼlonlar asosida hisoblangan.\n<a href=\"".e($url).'">Barcha narxlar</a>';

        return $this->publish('prices', $text, 0);
    }

    private function publish(string $kind, string $text, int $items): ChannelPost
    {
        $post = ChannelPost::create(['kind' => $kind, 'text' => $text, 'status' => ChannelPost::DRAFT, 'items_count' => $items]);
        $this->digest->publish($post);

        return $post;
    }
}
