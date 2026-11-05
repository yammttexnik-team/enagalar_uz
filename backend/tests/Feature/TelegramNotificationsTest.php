<?php

namespace Tests\Feature;

use App\Enums\ImportedListingStatus;
use App\Models\Article;
use App\Models\ChannelPost;
use App\Models\ImportedListing;
use App\Models\Region;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Telegram\ChannelPoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class TelegramNotificationsTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config([
            'services.telegram.bot_token' => '123:abc',
            'services.telegram.webhook_secret' => 'sec',
            'services.telegram.channel_id' => '@enagalar_rasmiy',
            'app.frontend_url' => 'https://enagalar.uz',
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 9]])]);
    }

    private function region(): Region
    {
        return Region::where('slug', 'tashkent-city')->firstOrFail();
    }

    private function nanny(array $user = []): User
    {
        $u = $this->nannyUser(['region_id' => $this->region()->id]);
        $u->update($user + ['telegram_id' => random_int(1000, 9999999)]);

        return $u;
    }

    private function listing(string $type, array $o = []): ImportedListing
    {
        return ImportedListing::create($o + [
            'type' => $type, 'status' => ImportedListingStatus::Active, 'source_channel' => 'x', 'source_url' => 'https://t.me/x/'.uniqid(), 'source_post_id' => random_int(1, 999999),
            'source_posted_at' => now()->subHour(), 'raw_text' => 'x', 'content_hash' => uniqid(),
            'title' => 'Asl sarlavha', 'title_uz' => 'Uzbekcha sarlavha', 'title_ru' => 'Русский заголовок',
            'region_id' => $this->region()->id, 'price_min' => 3_000_000, 'price_unit' => 'month',
            'published_at' => now()->subHour(),
        ]);
    }

    private function sentTexts(): array
    {
        return Http::recorded()->map(fn ($p) => $p[0])->filter(fn (Request $r) => str_ends_with($r->url(), '/sendMessage'))->values()->all();
    }

    public function test_nanny_gets_digest_with_translated_titles_button_and_footer(): void
    {
        $nanny = $this->nanny();
        $this->listing(ImportedListing::TYPE_VACANCY);
        Vacancy::factory()->active()->create(['parent_profile_id' => $this->parentUser()->parentProfile->id, 'title' => 'Enaga kerak <b>', 'region_id' => $this->region()->id]);
        // Boshqa viloyat va nanny turidagi e'lon kirmaydi
        $this->listing(ImportedListing::TYPE_VACANCY, ['region_id' => Region::where('slug', 'tashkent')->value('id')]);
        $this->listing(ImportedListing::TYPE_NANNY);

        Artisan::call('notify:matches');

        $sent = $this->sentTexts();
        $this->assertCount(1, $sent);
        $data = $sent[0]->data();
        $this->assertEquals($nanny->telegram_id, $data['chat_id']);
        $this->assertStringContainsString('Hududingizda 2 ta yangi eʼlon', $data['text']);
        $this->assertStringContainsString('Uzbekcha sarlavha', $data['text']);
        $this->assertStringContainsString('Enaga kerak &lt;b&gt;', $data['text']);
        $this->assertStringContainsString('/stop', $data['text']);
        $this->assertSame('https://enagalar.uz/vakansiyalar?region=tashkent-city', $data['reply_markup']['inline_keyboard'][0][0]['url']);
        $this->assertNotNull($nanny->fresh()->last_match_notified_at);

        // 20 soat ichida qayta yuborilmaydi
        Artisan::call('notify:matches');
        $this->assertCount(1, $this->sentTexts());
    }

    public function test_parent_gets_russian_digest_about_nannies_in_vacancy_region(): void
    {
        $parent = $this->parentUser();
        $parent->update(['telegram_id' => 4242, 'locale' => 'ru']);
        Vacancy::factory()->create(['parent_profile_id' => $parent->parentProfile->id, 'region_id' => $this->region()->id]);
        $this->listing(ImportedListing::TYPE_NANNY);
        $this->listing(ImportedListing::TYPE_VACANCY);

        Artisan::call('notify:matches');

        $sent = $this->sentTexts();
        $this->assertCount(1, $sent);
        $data = $sent[0]->data();
        $this->assertStringContainsString('новых объявлений: 1', $data['text']);
        $this->assertStringContainsString('Русский заголовок', $data['text']);
        $this->assertSame('https://enagalar.uz/ru/enagalar?region=tashkent-city', $data['reply_markup']['inline_keyboard'][0][0]['url']);
    }

    public function test_skips_stopped_old_window_blocked_and_empty(): void
    {
        $this->listing(ImportedListing::TYPE_VACANCY);
        $this->nanny(['notify_telegram' => false]);
        $this->nanny(['last_match_notified_at' => now()->subHours(5)]);
        $this->nanny(['status' => 'blocked']);
        $this->nanny(['telegram_id' => null]);
        // Yangi e'lon yo'q (oynadan oldingi)
        $late = $this->nanny(['last_match_notified_at' => now()->subHours(30)]);
        ImportedListing::query()->update(['source_posted_at' => now()->subHours(40)]);

        Artisan::call('notify:matches');

        $this->assertCount(0, $this->sentTexts());
        $this->assertTrue($late->fresh()->last_match_notified_at->lt(now()->subHours(29)));
    }

    public function test_403_disables_notifications(): void
    {
        Http::swap(new Factory);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden: bot was blocked by the user'], 403)]);
        $nanny = $this->nanny();
        $this->listing(ImportedListing::TYPE_VACANCY);

        Artisan::call('notify:matches');

        $nanny->refresh();
        $this->assertFalse($nanny->notify_telegram);
        $this->assertNull($nanny->last_match_notified_at);
    }

    public function test_stop_and_notify_commands(): void
    {
        $user = $this->nanny(['telegram_id' => 777]);
        $send = fn (string $text) => $this->postJson('/api/v1/telegram/webhook', [
            'message' => ['message_id' => 1, 'text' => $text, 'from' => ['id' => 777, 'is_bot' => false, 'first_name' => 'A', 'language_code' => 'uz'], 'chat' => ['id' => 777, 'type' => 'private']],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'sec'])->assertSuccessful();

        $send('/stop');
        $this->assertFalse($user->fresh()->notify_telegram);
        $this->assertStringContainsString('/notify', $this->sentTexts()[0]->data()['text']);

        $send('/start');
        $this->assertFalse($user->fresh()->notify_telegram);

        $send('/notify');
        $this->assertTrue($user->fresh()->notify_telegram);
    }

    public function test_nudges_once_for_incomplete_profiles(): void
    {
        $nanny = $this->nanny();
        $nanny->forceFill(['created_at' => now()->subDays(2)])->save();
        $parent = $this->parentUser();
        $parent->forceFill(['telegram_id' => 5151, 'created_at' => now()->subDays(2)])->save();
        $done = $this->parentUser();
        $done->forceFill(['telegram_id' => 6161, 'created_at' => now()->subDays(2)])->save();
        Vacancy::factory()->create(['parent_profile_id' => $done->parentProfile->id]);
        $fresh = $this->nanny();   // bugun ro'yxatdan o'tgan

        Artisan::call('notify:nudges');

        $sent = $this->sentTexts();
        $this->assertCount(2, $sent);
        $urls = collect($sent)->map(fn ($r) => $r->data()['reply_markup']['inline_keyboard'][0][0]['url'])->all();
        $this->assertContains('https://enagalar.uz/kabinet/anketa', $urls);
        $this->assertContains('https://enagalar.uz/kabinet/vakansiyalar/yangi', $urls);
        $this->assertStringContainsString('30 000', $sent[0]->data()['text']);
        $this->assertNull($fresh->fresh()->nudged_at);

        Artisan::call('notify:nudges');
        $this->assertCount(2, $this->sentTexts());
    }

    public function test_article_publish_posts_to_channel(): void
    {
        $article = Article::create([
            'slug' => 'enaga-narxi', 'topic_key' => 't1', 'title_uz' => 'Enaga narxi 2026', 'title_ru' => 'Цена', 'excerpt_uz' => 'Qisqa <mazmun>',
            'excerpt_ru' => 'x', 'body_uz' => 'b', 'body_ru' => 'b', 'status' => Article::PUBLISHED, 'published_at' => now(),
        ]);

        $post = app(ChannelPoster::class)->postArticle($article);

        $this->assertSame('article', $post->kind);
        $this->assertSame(ChannelPost::SENT, $post->status);
        $text = $this->sentTexts()[0]->data()['text'];
        $this->assertStringContainsString('https://enagalar.uz/maqolalar/enaga-narxi', $text);
        $this->assertStringContainsString('Qisqa &lt;mazmun&gt;', $text);
    }

    public function test_prices_post_skipped_without_data_and_sent_with_data(): void
    {
        Artisan::call('channel:prices');
        $this->assertSame(ChannelPost::SKIPPED, ChannelPost::where('kind', 'prices')->first()->status);
        $this->assertCount(0, $this->sentTexts());

        for ($i = 0; $i < 3; $i++) {
            $this->listing(ImportedListing::TYPE_NANNY, ['price_min' => 2_000_000 + $i * 500_000]);
        }
        Cache::flush();

        Artisan::call('channel:prices');

        $post = ChannelPost::where('kind', 'prices')->latest('id')->first();
        $this->assertSame(ChannelPost::SENT, $post->status);
        $text = $this->sentTexts()[0]->data()['text'];
        $this->assertStringContainsString('oylik median', $text);
        $this->assertStringContainsString('https://enagalar.uz/narxlar', $text);
    }
}
