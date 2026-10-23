<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\ChannelPosts\Pages\ListChannelPosts;
use App\Models\ChannelPost;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Telegram\ChannelDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class ChannelDigestTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config([
            'services.telegram.bot_token' => 'TOKEN',
            'services.telegram.channel_id' => '@enagalar_rasmiy',
            'services.telegram.admin_chat_id' => '777',
            'services.telegram.digest_auto' => false,
            'app.frontend_url' => 'https://enagalar.uz',
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 55]])]);
    }

    private function seedItems(int $vacancies = 2, int $nannies = 2): void
    {
        $parent = $this->parentUser();
        Vacancy::factory()->active()->count($vacancies)->create(['parent_profile_id' => $parent->parentProfile->id]);
        for ($i = 0; $i < $nannies; $i++) {
            $this->nannyUser()->nannyProfile->update(['status' => 'active', 'published_at' => now()]);
        }
    }

    public function test_digest_has_items_links_and_no_contacts(): void
    {
        $this->seedItems();

        $post = app(ChannelDigest::class)->build();

        $this->assertSame(ChannelPost::DRAFT, $post->status);
        $this->assertSame(4, $post->items_count);
        $this->assertStringContainsString('<a href="https://enagalar.uz/vakansiyalar/', $post->text);
        $this->assertStringContainsString('https://enagalar.uz/enagalar/', $post->text);
        $this->assertStringContainsString('———', $post->text);
        $this->assertStringContainsString('t.me/enagalaruz_bot?start=c_DIGEST', $post->text);
        $this->assertStringNotContainsString('dilnoza_secret', $post->text);
        $this->assertStringNotContainsString('+998', $post->text);
        $this->assertStringNotContainsString('@', $post->text);
        // vakansiyalar enagalardan oldin
        $this->assertLessThan(strpos($post->text, '/enagalar/'), strpos($post->text, '/vakansiyalar/'));
    }

    public function test_fewer_than_three_is_skipped_and_nothing_sent(): void
    {
        $this->seedItems(1, 1);

        $this->artisan('channel:digest')->assertSuccessful();

        $this->assertSame(ChannelPost::SKIPPED, ChannelPost::first()->status);
        Http::assertNothingSent();
    }

    public function test_manual_mode_saves_draft_and_previews_to_admin(): void
    {
        $this->seedItems();

        $this->artisan('channel:digest')->assertSuccessful();

        $this->assertSame(ChannelPost::DRAFT, ChannelPost::first()->status);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r['chat_id'] === '777' && str_contains($r['text'], '/admin/channel-posts'));
    }

    public function test_auto_mode_sends_to_channel(): void
    {
        config(['services.telegram.digest_auto' => true]);
        $this->seedItems();

        $this->artisan('channel:digest')->assertSuccessful();

        $post = ChannelPost::first();
        $this->assertSame(ChannelPost::SENT, $post->status);
        $this->assertSame(55, $post->telegram_message_id);
        Http::assertSent(fn (Request $r) => $r['chat_id'] === '@enagalar_rasmiy' && $r['disable_web_page_preview'] === true);
    }

    public function test_publish_failure_is_recorded(): void
    {
        Http::swap(new HttpFactory);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden: bot is not a member'], 403)]);
        $post = ChannelPost::create(['text' => 'x', 'status' => ChannelPost::DRAFT, 'items_count' => 3]);

        $this->assertFalse(app(ChannelDigest::class)->publish($post));
        $this->assertSame(ChannelPost::FAILED, $post->fresh()->status);
        $this->assertStringContainsString('Forbidden', $post->fresh()->error);
    }

    public function test_filament_publish_and_build_actions(): void
    {
        $this->actingAs(User::factory()->create()->assignRole(Role::Admin->value));
        $post = ChannelPost::create(['text' => 'x', 'status' => ChannelPost::DRAFT, 'items_count' => 3]);

        Livewire::test(ListChannelPosts::class)->assertSuccessful()
            ->callTableAction('publish', $post)
            ->callAction('build');

        $this->assertSame(ChannelPost::SENT, $post->fresh()->status);
        $this->assertSame(2, ChannelPost::count());
    }
}
