<?php

namespace Tests\Feature;

use App\Enums\ImportedListingStatus;
use App\Models\AiRequest;
use App\Models\Article;
use App\Models\ImportedListing;
use App\Models\Region;
use App\Models\Vacancy;
use App\Services\Content\ArticleTopics;
use App\Services\Content\ArticleWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array> Claude'ga yuborilgan kirishlar */
    public array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['services.anthropic.key' => 'sk-test', 'services.anthropic.enabled' => true]);
        $this->fakeWriter();
    }

    private function fakeWriter(): void
    {
        $test = $this;
        $this->app->bind(ArticleWriter::class, fn ($app) => new class($app->make(ArticleTopics::class), $test) extends ArticleWriter
        {
            public function __construct(ArticleTopics $topics, private readonly object $test)
            {
                parent::__construct($topics);
            }

            protected function callApi(string $system, string $userContent, string $model, array $schema): array
            {
                $this->test->sent[] = json_decode($userContent, true);
                $body = str_repeat('Bu juda foydali amaliy maslahat matni. ', 80);
                $data = [
                    'title_uz' => 'Toshkentda enaga narxlari 2026', 'title_ru' => 'Цены на нянь в Ташкенте 2026',
                    'excerpt_uz' => 'Qisqacha.', 'excerpt_ru' => 'Кратко.',
                    'body_uz' => "## Narxlar\n\n$body", 'body_ru' => "## Цены\n\n$body",
                ];

                return ['text' => json_encode($data, JSON_UNESCAPED_UNICODE), 'stop_reason' => 'end_turn', 'input_tokens' => 900, 'output_tokens' => 3000];
            }
        });
    }

    public function test_writer_creates_bilingual_published_article(): void
    {
        $article = app(ArticleWriter::class)->write('choose-nanny');

        $this->assertSame('toshkentda-enaga-narxlari-2026', $article->slug);
        $this->assertSame('published', $article->status);
        $this->assertNotNull($article->published_at);
        $this->assertSame('Цены на нянь в Ташкенте 2026', $article->title_ru);
        $this->assertStringContainsString('## Narxlar', $article->body_uz);
        $this->assertDatabaseHas('ai_requests', ['feature' => 'article', 'status' => 'ok', 'model' => 'claude-sonnet-5-5']);
    }

    public function test_slug_is_unique_and_oz_marks_removed(): void
    {
        Article::forceCreate([
            'slug' => 'toshkentda-enaga-narxlari-2026', 'title_uz' => 'x', 'title_ru' => 'x', 'excerpt_uz' => 'x',
            'excerpt_ru' => 'x', 'body_uz' => 'x', 'body_ru' => 'x', 'status' => 'published', 'published_at' => now(),
        ]);

        $this->assertSame('toshkentda-enaga-narxlari-2026-2', app(ArticleWriter::class)->write('choose-nanny')->slug);
    }

    public function test_command_picks_next_topic_and_is_idempotent(): void
    {
        $topics = app(ArticleTopics::class);
        $keys = $topics->keys();
        $this->assertCount(10 + Region::count(), $keys);
        $this->assertContains('price-'.Region::first()->slug, $keys);
        $this->assertSame($keys[0], $topics->next());

        $this->artisan('articles:generate')->assertSuccessful();
        $this->assertSame($keys[0], Article::first()->topic_key);
        $this->assertSame($keys[1], $topics->next());

        $this->artisan('articles:generate --count=2')->assertSuccessful();
        $this->assertSame(3, Article::count());
        $this->assertSame([$keys[0], $keys[1], $keys[2]], Article::orderBy('id')->pluck('topic_key')->all());

        // Bir mavzu uchun qayta yozilmaydi
        app(ArticleWriter::class)->write($keys[0]);
        $this->assertSame(3, Article::count());
        $this->assertSame(3, AiRequest::where('feature', 'article')->count());
    }

    public function test_command_skips_when_ai_disabled(): void
    {
        config(['services.anthropic.enabled' => false]);
        $this->artisan('articles:generate')->assertSuccessful();
        $this->assertSame(0, Article::count());
    }

    public function test_price_topic_sends_real_stats(): void
    {
        $region = Region::where('slug', 'samarqand')->first() ?? Region::skip(1)->first();
        $other = Region::where('id', '!=', $region->id)->first();

        $make = fn (int $price, array $extra = []) => ImportedListing::forceCreate($extra + [
            'type' => 'nanny', 'status' => ImportedListingStatus::Active, 'source_channel' => 'c', 'source_post_id' => random_int(1, 1e9),
            'source_url' => 'https://t.me/c/1', 'raw_text' => 'x', 'source_posted_at' => now(), 'region_id' => $region->id, 'title' => 't',
            'work_format' => 'full_time', 'price_min' => $price, 'price_unit' => 'month', 'content_hash' => bin2hex(random_bytes(8)),
            'expires_at' => now()->addDays(5),
        ]);
        $make(2_000_000);
        $make(3_000_000);
        $make(5_000_000);
        $make(1_000_000, ['status' => ImportedListingStatus::Archived, 'archived_at' => now()->subDays(100)]); // eski: kirmaydi
        $make(9_000_000, ['region_id' => $other->id]); // boshqa viloyat: kirmaydi
        $make(4_000_000, ['work_format' => 'hourly', 'price_unit' => 'hour']); // 1 ta: guruh kichik
        Vacancy::factory()->count(3)->active()->create(['region_id' => $region->id, 'salary_min' => 3_000_000, 'salary_max' => 5_000_000]);

        $article = app(ArticleWriter::class)->write('price-'.$region->slug);

        $stats = $this->sent[0]['stats'];
        $this->assertTrue($stats['enough_data']);
        $this->assertSame($region->name_uz, $this->sent[0]['region']['uz']);
        $this->assertSame($region->id, $article->region_id);

        $nanny = collect($stats['groups'])->firstWhere('side', 'nannies_ask');
        $this->assertSame(['month', 'full_time', 3, 3_000_000, 2_000_000, 5_000_000], [
            $nanny['price_unit'], $nanny['work_format'], $nanny['count'], $nanny['median'], $nanny['min'], $nanny['max'],
        ]);
        $parents = collect($stats['groups'])->firstWhere('side', 'parents_offer');
        $this->assertSame([3, 4_000_000], [$parents['count'], $parents['median']]);
        $this->assertCount(2, $stats['groups']); // soatbay guruh (1 ta) tushmaydi
    }

    public function test_price_stats_not_enough_data(): void
    {
        $stats = app(ArticleWriter::class)->priceStats(Region::first());
        $this->assertFalse($stats['enough_data']);
        $this->assertSame([], $stats['groups']);
    }

    public function test_public_api_lists_and_shows_published_only(): void
    {
        app(ArticleWriter::class)->write('choose-nanny');
        $draft = app(ArticleWriter::class)->write('interview-questions');
        $draft->update(['status' => 'draft']);

        $list = $this->getJson('/api/v1/articles')->assertOk();
        $list->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'toshkentda-enaga-narxlari-2026')
            ->assertJsonPath('data.0.title.ru', 'Цены на нянь в Ташкенте 2026')
            ->assertJsonPath('data.0.excerpt.uz', 'Qisqacha.')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.body');

        $this->getJson('/api/v1/articles/toshkentda-enaga-narxlari-2026')->assertOk()
            ->assertJsonPath('data.title.uz', 'Toshkentda enaga narxlari 2026')
            ->assertJsonStructure(['data' => ['slug', 'title', 'excerpt', 'published_at', 'body' => ['uz', 'ru']]]);

        $draft->update(['slug' => 'draft-one']);
        $this->getJson('/api/v1/articles/draft-one')->assertNotFound();
        $this->getJson('/api/v1/articles/yoq')->assertNotFound();
    }
}
