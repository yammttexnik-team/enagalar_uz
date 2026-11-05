<?php

namespace Tests\Feature;

use App\Enums\ImportedListingStatus;
use App\Models\AiRequest;
use App\Models\ImportedListing;
use App\Services\Imports\ListingTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingTranslationTest extends TestCase
{
    use RefreshDatabase;

    private int $calls = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.anthropic.enabled' => true, 'services.anthropic.key' => 'test']);
    }

    private function fakeTranslator(): void
    {
        $test = $this;
        $this->app->bind(ListingTranslator::class, fn () => new class($test) extends ListingTranslator
        {
            public function __construct(private readonly object $test) {}

            protected function callApi(string $system, string $userContent, string $model, array $schema): array
            {
                $this->test->calls++;
                $data = [
                    'title_uz' => 'Enaga kerak', 'description_uz' => 'Chilonzorda enaga kerak, tel 90 123 45 67.',
                    'title_ru' => 'Нужна няня', 'description_ru' => 'Нужна няня в Чиланзаре.',
                ];

                return ['text' => json_encode($data, JSON_UNESCAPED_UNICODE), 'stop_reason' => 'end_turn', 'input_tokens' => 100, 'output_tokens' => 50];
            }
        });
    }

    private function listing(array $attributes = []): ImportedListing
    {
        static $id = 900;
        $id++;

        return ImportedListing::create($attributes + [
            'type' => ImportedListing::TYPE_VACANCY, 'status' => ImportedListingStatus::Active,
            'source_channel' => 'enagalar', 'source_post_id' => $id, 'source_url' => "https://t.me/enagalar/$id",
            'source_posted_at' => now()->subDay(), 'expires_at' => now()->addDays(13), 'published_at' => now(),
            'raw_text' => 'Нужна няня', 'content_hash' => hash('sha256', (string) $id),
            'title' => 'Нужна няня в Чиланзар', 'description' => 'Ищем няню для ребенка 2 лет.',
        ]);
    }

    public function test_command_fills_missing_translations_only(): void
    {
        $this->fakeTranslator();
        $missing = $this->listing();
        $done = $this->listing(['title_uz' => 'a', 'description_uz' => 'b', 'title_ru' => 'c', 'description_ru' => 'd']);
        $removed = $this->listing(['status' => ImportedListingStatus::Removed]);

        $this->artisan('imports:translate')->assertSuccessful();

        $this->assertSame(1, $this->calls);
        $missing->refresh();
        $this->assertSame('Enaga kerak', $missing->title_uz);
        $this->assertSame('Нужна няня', $missing->title_ru);
        $this->assertStringNotContainsString('123 45 67', $missing->description_uz);
        $this->assertSame('a', $done->refresh()->title_uz);
        $this->assertNull($removed->refresh()->title_uz);
        $this->assertSame(1, AiRequest::where('feature', 'listing_translate')->count());
    }

    public function test_command_does_nothing_when_ai_disabled(): void
    {
        config(['services.anthropic.enabled' => false]);
        $this->fakeTranslator();
        $this->listing();

        $this->artisan('imports:translate')->assertSuccessful();

        $this->assertSame(0, $this->calls);
    }

    public function test_api_returns_text_by_accept_language(): void
    {
        $l = $this->listing(['title_uz' => 'Enaga kerak', 'description_uz' => 'Matn uz', 'title_ru' => 'Нужна няня', 'description_ru' => 'Текст ru']);

        $this->getJson('/api/v1/imported', ['Accept-Language' => 'ru'])->assertOk()
            ->assertJsonPath('data.0.title', 'Нужна няня')->assertJsonPath('data.0.description', 'Текст ru');
        $this->getJson("/api/v1/imported/{$l->id}", ['Accept-Language' => 'uz'])->assertOk()
            ->assertJsonPath('data.title', 'Enaga kerak')->assertJsonPath('data.original_title', 'Нужна няня');
        $this->getJson('/api/v1/imported')->assertOk()->assertJsonPath('data.0.title', 'Enaga kerak');
    }

    public function test_api_falls_back_to_original_without_translation(): void
    {
        $this->listing();

        $this->getJson('/api/v1/imported', ['Accept-Language' => 'uz'])->assertOk()
            ->assertJsonPath('data.0.title', 'Нужна няня в Чиланзар');
    }
}
