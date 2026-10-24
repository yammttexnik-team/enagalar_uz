<?php

namespace App\Http\Controllers;

use App\Models\NannyProfile;
use App\Models\Region;
use App\Models\Vacancy;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * /sitemap.xml — ochiq sahifalar (uz va ru), viloyat bo'yicha ro'yxatlar, faol anketa va vakansiyalar.
 * Telegramdan olingan e'lonlar kiritilmaydi (egasi tasdiqlamagan, tez eskiradi).
 */
class SitemapController extends Controller
{
    private const STATIC = ['/', '/enagalar', '/vakansiyalar', '/bonus-shartlari'];

    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    private function build(): string
    {
        $entries = [];
        foreach (self::STATIC as $path) {
            $entries[] = [$path, null];
        }
        foreach (Region::orderBy('sort')->pluck('slug') as $slug) {
            $entries[] = ['/enagalar?region='.$slug, null];
            $entries[] = ['/vakansiyalar?region='.$slug, null];
        }
        NannyProfile::active()->select(['id', 'updated_at'])->orderBy('id')
            ->each(function ($p) use (&$entries) {
                $entries[] = ['/enagalar/'.$p->id, $p->updated_at];
            });
        Vacancy::active()->select(['id', 'updated_at'])->orderBy('id')
            ->each(function ($v) use (&$entries) {
                $entries[] = ['/vakansiyalar/'.$v->id, $v->updated_at];
            });

        $base = rtrim((string) config('app.frontend_url'), '/');
        $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        foreach ($entries as [$path, $updated]) {
            $uz = e($base.$path);
            $ru = e($base.'/ru'.($path === '/' ? '' : $path));
            foreach ([$uz, $ru] as $loc) {
                $out .= "  <url><loc>$loc</loc>"
                    .($updated ? '<lastmod>'.$updated->toAtomString().'</lastmod>' : '')
                    ."<xhtml:link rel=\"alternate\" hreflang=\"uz\" href=\"$uz\"/>"
                    ."<xhtml:link rel=\"alternate\" hreflang=\"ru\" href=\"$ru\"/>"
                    ."</url>\n";
            }
        }

        return $out.'</urlset>'."\n";
    }
}
