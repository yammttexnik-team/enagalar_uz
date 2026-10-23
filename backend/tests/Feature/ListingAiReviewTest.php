<?php

namespace Tests\Feature;

use App\Enums\ImportedListingStatus;
use App\Models\ImportedListing;
use App\Services\Imports\ListingImporter;
use App\Services\Imports\TelegramChannelReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ListingAiReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow('2026-10-23 12:00:00');
        config(['services.anthropic.key' => 'sk-test', 'services.anthropic.enabled' => true, 'imports.ai_review' => true]);
    }

    /** Claude'ning bitta javobini qaytaradigan importer bilan postni import qiladi. */
    private function importWith(array $response, int $id = 1): string
    {
        $this->app->bind(ListingImporter::class, fn ($app) => new class($app->make(TelegramChannelReader::class), $response) extends ListingImporter
        {
            public function __construct(TelegramChannelReader $reader, private readonly array $response)
            {
                parent::__construct($reader);
            }

            protected function callApi(string $system, string $userContent, string $model, array $schema): array
            {
                return ['text' => json_encode($this->response, JSON_UNESCAPED_UNICODE), 'stop_reason' => 'end_turn', 'input_tokens' => 400, 'output_tokens' => 150];
            }
        });

        return app(ListingImporter::class)->importPost([
            'id' => $id, 'channel' => 'enagalar', 'url' => "https://t.me/enagalar/$id",
            'posted_at' => Carbon::parse('2026-10-22 10:00:00'),
            'text' => "Enaga kerak! Chilonzor, 2 yoshli qizimizga. Oyiga 3 mln. Post raqami $id", 'edited' => false,
        ]);
    }

    private function ai(array $overrides = []): array
    {
        return $overrides + [
            'kind' => 'vacancy', 'title' => 'Ikki yoshli qizaloqqa enaga kerak',
            'description' => "Chilonzorda 2 yoshli qizimizga mehribon enaga qidiryapmiz. Oyiga 3 mln so'm.",
            'region' => 'tashkent-city', 'district' => 'Chilonzor', 'work_format' => 'full_time', 'schedule' => '',
            'price_min' => 3000000, 'price_max' => 0, 'price_unit' => 'month', 'children_count' => 1, 'children_ages' => [2],
            'age_groups' => [], 'languages' => [], 'experience_years' => 0, 'person_age' => 0, 'live_in' => false, 'is_urgent' => false,
            'review_decision' => 'approve', 'review_reason' => "Haqiqiy e'lon, joy va narx ko'rsatilgan.",
        ];
    }

    public function test_approve_publishes_listing(): void
    {
        $this->importWith($this->ai());

        $listing = ImportedListing::sole();
        $this->assertSame(ImportedListingStatus::Active, $listing->status);
        $this->assertSame('approve', $listing->ai_decision);
        $this->assertNotNull($listing->ai_reason);
        $this->assertNotNull($listing->ai_reviewed_at);
        $this->assertNotNull($listing->published_at);
        $this->assertTrue($listing->expires_at->eq(Carbon::parse('2026-10-22 10:00:00')->addDays(ImportedListing::LIFETIME_DAYS)));
    }

    public function test_reject_marks_listing_rejected(): void
    {
        $this->importWith($this->ai(['review_decision' => 'reject', 'review_reason' => 'Reklama.']));

        $listing = ImportedListing::sole();
        $this->assertSame(ImportedListingStatus::Rejected, $listing->status);
        $this->assertSame('reject', $listing->ai_decision);
        $this->assertNull($listing->published_at);
    }

    public function test_manual_keeps_listing_pending(): void
    {
        $this->importWith($this->ai(['review_decision' => 'manual']));

        $listing = ImportedListing::sole();
        $this->assertSame(ImportedListingStatus::Pending, $listing->status);
        $this->assertSame('manual', $listing->ai_decision);
        $this->assertNull($listing->published_at);
    }

    public function test_phone_in_description_forces_manual(): void
    {
        $this->importWith($this->ai(['description' => "Enaga kerak, Chilonzor. Tel 90 123 45 67 ga qo'ng'iroq qiling."]));

        $listing = ImportedListing::sole();
        $this->assertSame(ImportedListingStatus::Pending, $listing->status);
        $this->assertSame('manual', $listing->ai_decision);
    }

    public function test_review_off_keeps_old_auto_publish_behaviour(): void
    {
        config(['imports.ai_review' => false, 'imports.auto_publish' => false]);
        $this->importWith($this->ai());

        $listing = ImportedListing::sole();
        $this->assertSame(ImportedListingStatus::Pending, $listing->status);
        $this->assertNull($listing->ai_decision);

        config(['imports.auto_publish' => true]);
        $this->importWith($this->ai(), 3);
        $this->assertSame(ImportedListingStatus::Active, ImportedListing::where('source_post_id', 3)->first()->status);
    }
}
