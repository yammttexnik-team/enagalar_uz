<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_public_pages_in_both_languages(): void
    {
        $this->seed();
        config(['app.frontend_url' => 'https://enagalar.uz']);

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();
        $this->assertStringContainsString('<loc>https://enagalar.uz/enagalar</loc>', $xml);
        $this->assertStringContainsString('<loc>https://enagalar.uz/ru/vakansiyalar</loc>', $xml);
        $this->assertStringContainsString('/enagalar?region=tashkent-city', $xml);
        $this->assertStringNotContainsString('/elon/', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }
}
