<?php

namespace Tests\Feature;

use App\Enums\ImportedListingStatus;
use App\Enums\Role;
use App\Filament\Resources\ImportedListings\Pages\ListImportedListings;
use App\Filament\Resources\ImportedListings\Pages\ViewImportedListing;
use App\Filament\Resources\ImportSources\Pages\ListImportSources;
use App\Filament\Support\OwnerOutreach;
use App\Filament\Widgets\ModerationQueue;
use App\Models\AiRequest;
use App\Models\ImportedListing;
use App\Models\ImportSource;
use App\Models\Region;
use App\Models\User;
use App\Services\Imports\ListingImporter;
use App\Services\Imports\TelegramChannelReader;
use App\Services\Imports\TelegramUnavailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ListingImportTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> Claude'ga yuborilgan matnlar */
    public array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow('2026-10-23 12:00:00');
        config(['services.anthropic.key' => 'sk-test', 'services.anthropic.enabled' => true]);
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/telegram/$name.html"));
    }

    private function embed(string $text): string
    {
        return str_replace('{{TEXT}}', $text, $this->fixture('post_embed'));
    }

    /** Claude javobi: post matniga qarab (closure) yoki bitta javob. */
    private function fakeClaude(array|\Closure $response): void
    {
        $test = $this;
        $this->app->bind(ListingImporter::class, fn ($app) => new class($app->make(TelegramChannelReader::class), $response, $test) extends ListingImporter
        {
            public function __construct(TelegramChannelReader $reader, private readonly array|\Closure $response, private readonly object $test)
            {
                parent::__construct($reader);
            }

            protected function callApi(string $system, string $userContent, string $model, array $schema): array
            {
                $text = json_decode($userContent, true)['text'];
                $this->test->sent[] = $text;
                $data = $this->response instanceof \Closure ? ($this->response)($text) : $this->response;

                return ['text' => json_encode($data, JSON_UNESCAPED_UNICODE), 'stop_reason' => 'end_turn', 'input_tokens' => 400, 'output_tokens' => 150];
            }
        });
    }

    private function aiResult(array $overrides = []): array
    {
        return $overrides + [
            'kind' => 'vacancy', 'title' => 'Ikki yoshli qizaloqqa enaga kerak',
            'description' => "Chilonzorda 2 yoshli qizimizga mehribon enaga qidiryapmiz. Oyiga 3 mln so'm.",
            'region' => 'tashkent-city', 'district' => 'Chilonzor', 'work_format' => 'full_time', 'schedule' => '',
            'price_min' => 3000000, 'price_max' => 0, 'price_unit' => 'month', 'children_count' => 1, 'children_ages' => [2],
            'age_groups' => [], 'languages' => [], 'experience_years' => 0, 'person_age' => 0, 'live_in' => false, 'is_urgent' => false,
        ];
    }

    private function tgPost(int $id, string $text, string $at = '2026-10-22 10:00:00'): array
    {
        return ['id' => $id, 'channel' => 'enagalar', 'url' => "https://t.me/enagalar/$id", 'posted_at' => Carbon::parse($at), 'text' => $text, 'edited' => false];
    }

    private function listing(array $attributes = []): ImportedListing
    {
        static $id = 500;
        $id++;

        return ImportedListing::create($attributes + [
            'type' => ImportedListing::TYPE_VACANCY, 'status' => ImportedListingStatus::Active,
            'source_channel' => 'enagalar', 'source_post_id' => $id, 'source_url' => "https://t.me/enagalar/$id",
            'source_posted_at' => now()->subDay(), 'expires_at' => now()->addDays(13),
            'raw_text' => 'Enaga kerak, tel 90 123 45 67', 'content_hash' => hash('sha256', (string) $id),
            'title' => 'Enaga kerak', 'description' => 'Enaga kerak',
        ]);
    }

    public function test_reader_parses_channel_pages_with_pagination(): void
    {
        Http::fake([
            't.me/s/enagalar?before=100' => Http::response($this->fixture('channel_older')),
            't.me/s/enagalar' => Http::response($this->fixture('channel')),
        ]);

        $posts = app(TelegramChannelReader::class)->fetchChannel('enagalar');

        // 101 — faqat rasm, 98 — 7 kundan eski, 1 — xizmat xabari
        $this->assertSame([99, 100, 102], array_column($posts, 'id'));
        $vacancy = $posts[1];
        $this->assertSame("Enaga kerak!\nChilonzor tumani, 2 yoshli qizimizga.\nOyiga 3 mln so'm. Tel: +998 90 123 45 67\n\n@enagalar kanaliga obuna bo'ling", $vacancy['text']);
        $this->assertSame('https://t.me/enagalar/100', $vacancy['url']);
        $this->assertTrue($vacancy['posted_at']->eq(Carbon::parse('2026-10-22T05:30:00+00:00')));
        $this->assertFalse($vacancy['edited']);

        $this->assertTrue($posts[2]['edited']);
        $this->assertStringContainsString('😊 люблю детей', $posts[2]['text']);
        $this->assertStringContainsString('сюда (t.me/dilnoza_nanny)', $posts[2]['text']);

        Http::assertSent(fn (Request $r) => str_contains($r->header('User-Agent')[0] ?? '', 'EnagalarBot'));
    }

    public function test_reader_stops_at_last_seen_post_and_reads_single_post(): void
    {
        Http::fake([
            't.me/s/enagalar*' => Http::response($this->fixture('channel')),
            't.me/enagalar/100*' => Http::response($this->embed('Enaga kerak<br/>Topildi!')),
            't.me/enagalar/7*' => Http::response($this->fixture('post_not_found')),
            't.me/enagalar/8*' => Http::response('Bad gateway', 502),
        ]);
        $reader = app(TelegramChannelReader::class);

        $this->assertSame([102], array_column($reader->fetchChannel('enagalar', 100), 'id'));
        Http::assertSentCount(1); // kursorga yetdi — keyingi sahifa so'ralmaydi

        $this->assertSame("Enaga kerak\nTopildi!", $reader->fetchPost('enagalar', 100));
        $this->assertNull($reader->fetchPost('enagalar', 7));

        $this->expectException(TelegramUnavailable::class);
        $reader->fetchPost('enagalar', 8);
    }

    public function test_importer_creates_pending_listing_with_contact_hashes(): void
    {
        $this->fakeClaude($this->aiResult());
        $raw = "Enaga kerak! Chilonzor, 2 yoshli qizimizga. Oyiga 3 mln.\nTel: 90 123 45 67, @Malika_ona\n@enagalar kanaliga obuna bo'ling";

        $result = app(ListingImporter::class)->importPost($this->tgPost(100, $raw), ImportSource::where('channel', 'enagalar')->first());

        $this->assertSame(ListingImporter::CREATED, $result);
        $listing = ImportedListing::sole();
        $this->assertSame(ImportedListingStatus::Pending, $listing->status);
        $this->assertNull($listing->published_at);
        $this->assertSame(ImportedListing::TYPE_VACANCY, $listing->type);
        $this->assertSame(Region::where('slug', 'tashkent-city')->value('id'), $listing->region_id);
        $this->assertNotNull($listing->district_id);
        $this->assertSame(3000000, $listing->price_min);
        $this->assertTrue($listing->expires_at->eq(Carbon::parse('2026-10-22 10:00:00')->addDays(ImportedListing::LIFETIME_DAYS)));

        // Kontakt faqat xesh ko'rinishida; kanal belgisi (@enagalar) kontakt emas
        $this->assertContains(ImportedListing::contactHash('90 123 45 67'), $listing->contact_hashes);
        $this->assertContains(ImportedListing::contactHash('@malika_ona'), $listing->contact_hashes);
        $this->assertNotContains(ImportedListing::contactHash('@enagalar'), $listing->contact_hashes);
        $this->assertCount(2, $listing->contact_hashes);

        foreach ([$listing->title, $listing->description, $this->sent[0]] as $public) {
            $this->assertStringNotContainsString('123 45 67', $public);
            $this->assertStringNotContainsString('Malika_ona', $public);
        }
        $stored = DB::table('imported_listings')->value('raw_text');
        $this->assertStringNotContainsString('123 45 67', $stored);
        $this->assertSame($raw, $listing->raw_text);
        $this->assertSame(1, AiRequest::where('feature', ListingImporter::FEATURE)->count());
    }

    public function test_auto_publish_makes_listing_active(): void
    {
        config(['imports.ai_review' => false, 'imports.auto_publish' => true]);
        $this->fakeClaude($this->aiResult(['kind' => 'nanny', 'title' => '', 'description' => '']));

        app(ListingImporter::class)->importPost($this->tgPost(5, 'Tajribali enagaman, ish qidiryapman. Bolalarni yaxshi ko\'raman.'));

        $listing = ImportedListing::sole();
        $this->assertSame(ImportedListingStatus::Active, $listing->status);
        $this->assertNotNull($listing->published_at);
        $this->assertSame('Enaga ish qidiryapti', $listing->title);
        $this->assertNotSame('', $listing->description);
    }

    public function test_duplicates_update_existing_listing_instead_of_creating(): void
    {
        $this->fakeClaude($this->aiResult());
        $importer = app(ListingImporter::class);
        $text = 'Enaga kerak! Chilonzor, 2 yoshli qizimizga. Tel: +998 90 123 45 67';

        $importer->importPost($this->tgPost(100, $text, '2026-10-20 10:00:00'));

        // Xuddi shu matn boshqa kanalda — AI chaqirilmaydi, manba yangilanadi
        $this->assertSame(ListingImporter::UPDATED, $importer->importPost(
            ['channel' => 'enaga_ish', 'url' => 'https://t.me/enaga_ish/7'] + $this->tgPost(7, $text, '2026-10-21 10:00:00'),
        ));
        // Boshqa matn, lekin kontakt bir xil va turi bir xil
        $this->assertSame(ListingImporter::UPDATED, $importer->importPost(
            $this->tgPost(120, "Qayta yozaman: enaga kerak, 2 yoshli qizim bor.\n901234567", '2026-10-22 10:00:00'),
        ));
        // Bazadagi post — o'tkazib yuboriladi
        $this->assertSame(ListingImporter::EXISTS, $importer->importPost($this->tgPost(120, 'Enaga kerak, boshqa matn bilan')));

        $listing = ImportedListing::sole();
        $this->assertSame(120, (int) $listing->source_post_id);
        $this->assertSame('https://t.me/enagalar/120', $listing->source_url);
        $this->assertTrue($listing->expires_at->eq(Carbon::parse('2026-10-22 10:00:00')->addDays(ImportedListing::LIFETIME_DAYS)));
        $this->assertCount(2, $this->sent); // birinchi va uchinchi post
    }

    public function test_other_and_non_childcare_posts_are_skipped(): void
    {
        $this->fakeClaude(fn () => $this->aiResult(['kind' => 'other']));
        $importer = app(ListingImporter::class);

        $this->assertSame(ListingImporter::FILTERED, $importer->importPost($this->tgPost(1, 'Do\'konga sotuvchi kerak, maosh 4 mln. Tel 90 111 22 33')));
        $this->assertSame(ListingImporter::FILTERED, $importer->importPost($this->tgPost(2, 'Kanalimizga obuna bo\'ling! Bolalar kiyimlari reklama')));
        $this->assertSame(ListingImporter::OTHER, $importer->importPost($this->tgPost(3, 'Enagalar uchun kurs: 1-noyabrdan boshlanadi, joylar soni cheklangan')));

        $this->assertSame(0, ImportedListing::count());
        $this->assertCount(1, $this->sent);
    }

    public function test_fetch_command_imports_source_and_moves_cursor(): void
    {
        Http::fake([
            't.me/s/enagalar?before=100' => Http::response($this->fixture('channel_older')),
            't.me/s/enagalar' => Http::response($this->fixture('channel')),
            '*' => Http::response('', 500),
        ]);
        $this->fakeClaude(fn (string $text) => str_contains($text, 'Ищу работу')
            ? $this->aiResult(['kind' => 'nanny', 'title' => 'Опытная няня ищет работу', 'description' => 'Няня с опытом 5 лет.'])
            : $this->aiResult(['title' => 'Enaga kerak '.mb_substr($text, 0, 10)]));
        ImportSource::where('channel', '!=', 'enagalar')->update(['is_active' => false]);

        $this->artisan('imports:fetch')->assertSuccessful();

        $this->assertSame(3, ImportedListing::count());
        $source = ImportSource::where('channel', 'enagalar')->first();
        $this->assertSame(102, (int) $source->last_post_id);
        $this->assertNotNull($source->last_fetched_at);
        $nanny = ImportedListing::where('type', ImportedListing::TYPE_NANNY)->sole();
        $this->assertSame([ImportedListing::contactHash('@dilnoza_nanny')], $nanny->contact_hashes);
    }

    public function test_ai_budget_limits_calls_per_run(): void
    {
        config(['imports.max_ai_per_run' => 1]);
        $this->fakeClaude(fn (string $text) => $this->aiResult(['title' => mb_substr($text, 0, 20)]));
        $importer = app(ListingImporter::class);

        $importer->importPost($this->tgPost(1, 'Birinchi: enaga kerak Sergelida, 3 yoshli o\'g\'limga'));
        $this->assertSame(ListingImporter::LIMIT, $importer->importPost($this->tgPost(2, 'Ikkinchi: enaga kerak Yunusobodda, chaqaloqqa')));
        $this->assertCount(1, $this->sent);
    }

    public function test_check_command_archives_deleted_found_and_expired_but_keeps_on_network_error(): void
    {
        $gone = $this->listing(['source_post_id' => 10, 'source_url' => 'https://t.me/enagalar/10']);
        $found = $this->listing(['source_post_id' => 11, 'source_url' => 'https://t.me/enagalar/11', 'raw_text' => 'Enaga kerak, Chilonzor']);
        $alive = $this->listing(['source_post_id' => 12, 'source_url' => 'https://t.me/enagalar/12', 'raw_text' => 'Kunduzi bandman, kechqurun enaga kerak']);
        $flaky = $this->listing(['source_post_id' => 13, 'source_url' => 'https://t.me/enagalar/13', 'status' => ImportedListingStatus::Pending]);
        $expired = $this->listing(['source_post_id' => 14, 'expires_at' => now()->subHour()]);

        Http::fake([
            't.me/enagalar/10*' => Http::response($this->fixture('post_not_found')),
            't.me/enagalar/11*' => Http::response($this->embed('Enaga kerak, Chilonzor<br/><b>TOPILDI</b>')),
            't.me/enagalar/12*' => Http::response($this->embed('Kunduzi bandman, kechqurun enaga kerak')),
            't.me/enagalar/13*' => Http::failedConnection(),
            '*' => Http::response('', 503),
        ]);

        $this->artisan('imports:check')->assertSuccessful();

        $this->assertSame('deleted', $gone->fresh()->archive_reason);
        $this->assertSame(ImportedListingStatus::Archived, $gone->fresh()->status);
        $this->assertSame('found', $found->fresh()->archive_reason);
        $this->assertSame(ImportedListingStatus::Active, $alive->fresh()->status); // "bandman" — belgi emas
        $this->assertNotNull($alive->fresh()->last_checked_at);
        $this->assertSame(ImportedListingStatus::Pending, $flaky->fresh()->status);
        $this->assertNull($flaky->fresh()->last_checked_at);
        $this->assertSame('expired', $expired->fresh()->archive_reason);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/enagalar/14'));
    }

    public function test_moderator_approves_imported_listing_and_bulk_approves(): void
    {
        $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
        $one = $this->listing(['status' => ImportedListingStatus::Pending]);
        $two = $this->listing(['status' => ImportedListingStatus::Pending]);
        $three = $this->listing(['status' => ImportedListingStatus::Pending]);

        $this->actingAs($moderator);
        $this->get('/admin/imported-listings')->assertOk();
        $this->get("/admin/imported-listings/{$one->id}")->assertOk()->assertSee('tel 90 123 45 67');
        $this->get('/admin/import-sources')->assertForbidden(); // kanallar faqat admin uchun

        Livewire::test(ViewImportedListing::class, ['record' => $one->getRouteKey()])->callAction('approve');
        $this->assertSame(ImportedListingStatus::Active, $one->fresh()->status);
        $this->assertNotNull($one->fresh()->published_at);

        Livewire::test(ListImportedListings::class)->callTableBulkAction('approveSelected', [$two, $three]);
        $this->assertSame(ImportedListingStatus::Active, $two->fresh()->status);
        $this->assertSame(ImportedListingStatus::Active, $three->fresh()->status);

        Livewire::test(ViewImportedListing::class, ['record' => $one->getRouteKey()])->callAction('archive');
        $this->assertSame('admin', $one->fresh()->archive_reason);
        Livewire::test(ViewImportedListing::class, ['record' => $one->getRouteKey()])->callAction('restore');
        $this->assertSame(ImportedListingStatus::Active, $one->fresh()->status);
        $this->assertNull($one->fresh()->archive_reason);
    }

    public function test_moderator_writes_to_owner_once_per_contact(): void
    {
        $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
        $hash = [ImportedListing::contactHash('90 123 45 67')];
        $one = $this->listing(['raw_text' => 'Enaga kerak, tel 90 123 45 67, @Malika_ona', 'contact_hashes' => $hash]);
        $two = $this->listing(['contact_hashes' => $hash]);

        $labels = collect(OwnerOutreach::contacts($one))->pluck('label')->all();
        $this->assertSame(['@Malika_ona', '+998901234567'], $labels);
        $text = OwnerOutreach::message($one);
        $this->assertStringContainsString('/elon/'.$one->id.'?c=CLAIM', $text);
        $this->assertStringContainsString('@enagalar', $text);
        $this->assertSame('ru', OwnerOutreach::language($this->listing(['raw_text' => 'Ищем няню для ребёнка, тел 90 123 45 67'])));

        $this->actingAs($moderator);
        Livewire::test(ViewImportedListing::class, ['record' => $one->getRouteKey()])->callAction('outreach');
        $this->assertNotNull($one->fresh()->outreach_at);
        $this->assertSame($moderator->id, $one->fresh()->outreach_by);

        // Shu kontaktning boshqa e'loni orqali qayta yozilmaydi
        Livewire::test(ViewImportedListing::class, ['record' => $two->getRouteKey()])->assertActionHidden('outreach');
    }

    public function test_moderator_confirms_or_dismisses_anonymous_removal_request(): void
    {
        $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
        $one = $this->listing(['removal_requested_at' => now(), 'removal_reason' => 'Topildi']);
        $two = $this->listing(['removal_requested_at' => now()]);

        $this->actingAs($moderator);
        Livewire::test(ViewImportedListing::class, ['record' => $one->getRouteKey()])->callAction('confirmRemoval');
        $this->assertSame(ImportedListingStatus::Removed, $one->fresh()->status);

        Livewire::test(ViewImportedListing::class, ['record' => $two->getRouteKey()])->callAction('dismissRemoval');
        $this->assertSame(ImportedListingStatus::Active, $two->fresh()->status);
        $this->assertNull($two->fresh()->removal_requested_at);
    }

    public function test_admin_manages_import_sources(): void
    {
        $admin = User::factory()->create()->assignRole(Role::Admin->value);
        $this->actingAs($admin);

        $this->get('/admin/import-sources')->assertOk()->assertSee('@enaga_samarqand');
        $this->get('/admin/import-sources/create')->assertOk();
        Livewire::test(ModerationQueue::class)->assertOk()->assertSee('Import navbati');
        $this->assertSame(6, ImportSource::count());

        // "Hozir olish": bitta kanal darhol import qilinadi
        Http::fake(['t.me/s/enagalar*' => Http::response($this->fixture('channel')), '*' => Http::response('', 500)]);
        $this->fakeClaude($this->aiResult());
        $source = ImportSource::where('channel', 'enagalar')->first();
        Livewire::test(ListImportSources::class)->callTableAction('fetchNow', $source)->assertNotified();
        $this->assertSame(2, ImportedListing::count()); // keyingi sahifa xatosi olingan postlarni bekor qilmaydi
        $this->assertSame(102, (int) $source->fresh()->last_post_id);
    }
}
