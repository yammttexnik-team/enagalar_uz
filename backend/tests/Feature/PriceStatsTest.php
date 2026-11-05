<?php

namespace Tests\Feature;

use App\Enums\ImportedListingStatus;
use App\Models\ImportedListing;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PriceStatsTest extends TestCase
{
    use RefreshDatabase;

    private int $post = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
    }

    private function listing(int $min, ?int $max = null, array $attrs = []): ImportedListing
    {
        $id = $this->post++;

        return ImportedListing::create($attrs + [
            'type' => 'nanny',
            'status' => ImportedListingStatus::Active,
            'source_channel' => 'x',
            'source_post_id' => $id,
            'source_url' => 'https://t.me/x/'.$id,
            'source_posted_at' => now()->subDays(5),
            'raw_text' => 'matn',
            'content_hash' => hash('sha256', (string) $id),
            'title' => 'Enaga',
            'region_id' => Region::value('id'),
            'work_format' => 'full_time',
            'price_min' => $min,
            'price_max' => $max,
            'price_unit' => 'month',
            'contact_hashes' => [],
        ]);
    }

    public function test_stats_are_computed(): void
    {
        foreach ([2_000_000, 3_000_000, 4_000_000, 5_000_000] as $p) {
            $this->listing($p);
        }
        $this->listing(3_000_000, 5_000_000); // o'rtachasi 4 000 000

        $b = $this->getJson('/api/v1/prices')->assertOk()->json('data');

        $this->assertSame(5, $b['sample_size']);
        $m = $b['units']['month']['nanny'];
        $this->assertSame(5, $m['count']);
        $this->assertSame(4_000_000, $m['median']);
        $this->assertSame(3_000_000, $m['p25']);
        $this->assertSame(4_000_000, $m['p75']);
        $this->assertSame(2_000_000, $m['min']);
        $this->assertSame(5_000_000, $m['max']);
        $this->assertNull($b['units']['month']['vacancy']);
    }

    public function test_outliers_and_old_or_inactive_listings_are_excluded(): void
    {
        foreach ([3_000_000, 3_000_000, 3_000_000] as $p) {
            $this->listing($p);
        }
        $this->listing(100_000);                 // juda past
        $this->listing(90_000_000);              // juda baland
        $this->listing(3_000_000, null, ['source_posted_at' => now()->subDays(120)]);
        $this->listing(3_000_000, null, ['status' => ImportedListingStatus::Rejected]);
        $this->listing(10_000, null, ['price_unit' => 'hour']); // soatiga 10 000 normal, lekin 1 ta

        $data = $this->getJson('/api/v1/prices')->json('data');

        $this->assertSame(3, $data['units']['month']['nanny']['count']);
        $this->assertSame(4, $data['sample_size']);
    }

    public function test_buckets_below_three_are_hidden(): void
    {
        $this->listing(3_000_000);
        $this->listing(4_000_000);

        $data = $this->getJson('/api/v1/prices')->json('data');

        $this->assertNull($data['units']['month']['nanny']);
        $this->assertSame([], $data['regions']);
    }

    public function test_region_filter_and_overview(): void
    {
        [$a, $b] = Region::orderBy('sort')->take(2)->get();
        foreach ([2_000_000, 3_000_000, 4_000_000] as $p) {
            $this->listing($p, null, ['region_id' => $a->id]);
        }
        foreach ([6_000_000, 7_000_000, 8_000_000] as $p) {
            $this->listing($p, null, ['region_id' => $b->id, 'type' => 'vacancy']);
        }

        $all = $this->getJson('/api/v1/prices')->json('data');
        $this->assertSame(6, $all['sample_size']);
        $this->assertSame($b->slug, $all['regions'][0]['slug']);
        $this->assertSame(7_000_000, $all['regions'][0]['median']);

        $one = $this->getJson('/api/v1/prices?region='.$a->slug)->json('data');
        $this->assertSame(3, $one['sample_size']);
        $this->assertSame(3_000_000, $one['units']['month']['nanny']['median']);
        $this->assertNull($one['units']['month']['vacancy']);
        $this->assertCount(2, $one['regions']);

        $this->getJson('/api/v1/prices?region=yoq')->assertNotFound();
    }
}
