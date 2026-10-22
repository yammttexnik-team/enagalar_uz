<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Events\ConversationRead;
use App\Events\ConversationUpdated;
use App\Events\MessageDeleted;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Jobs\SendChatTelegramNotification;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Report;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Telegram\TelegramBot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    private User $parent;

    private User $nanny;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake(Message::DISK);
        Event::fake([MessageSent::class, MessageUpdated::class, MessageDeleted::class, ConversationRead::class, ConversationUpdated::class]);

        $this->parent = $this->parentUser();
        $this->nanny = $this->nannyUser(['status' => 'active']);
    }

    private function chat(): Conversation
    {
        return Conversation::between($this->parent, $this->nanny);
    }

    private function say(Conversation $c, User $from, string $body = 'Salom'): Message
    {
        return $c->messages()->create(['sender_id' => $from->id, 'body' => $body]);
    }

    // --- Suhbat boshlash ---

    public function test_parent_starts_with_active_nanny_and_reuses(): void
    {
        Sanctum::actingAs($this->parent);
        $id = $this->nanny->nannyProfile->id;

        $this->postJson('/api/v1/conversations', ['nanny_profile_id' => $id])
            ->assertCreated()
            ->assertJsonPath('data.other.id', $this->nanny->id)
            ->assertJsonPath('data.other.role', 'nanny')
            ->assertJsonPath('data.other.nanny_profile_id', $id)
            ->assertJsonPath('data.other.name', 'Dilnoza K.')
            ->assertJsonPath('data.other.initials', 'DK')
            ->assertJsonPath('data.unread_count', 0);

        $this->postJson('/api/v1/conversations', ['nanny_profile_id' => $id])->assertOk();
        $this->assertSame(1, Conversation::count());
    }

    public function test_start_requires_one_target_and_active_profile(): void
    {
        Sanctum::actingAs($this->parent);
        $this->postJson('/api/v1/conversations', [])->assertUnprocessable();

        $draft = $this->nannyUser(['status' => 'draft'], 'Boshqa Enaga');
        $this->postJson('/api/v1/conversations', ['nanny_profile_id' => $draft->nannyProfile->id])->assertUnprocessable();
    }

    public function test_nanny_cannot_start_with_nanny_profile_and_parent_cannot_use_vacancy(): void
    {
        $vacancy = Vacancy::factory()->active()->create(['parent_profile_id' => $this->parent->parentProfile->id]);

        Sanctum::actingAs($this->nanny);
        $this->postJson('/api/v1/conversations', ['nanny_profile_id' => $this->nanny->nannyProfile->id])->assertForbidden();

        Sanctum::actingAs($this->parent);
        $this->postJson('/api/v1/conversations', ['vacancy_id' => $vacancy->id])->assertForbidden();
    }

    public function test_nanny_starts_with_active_vacancy_only(): void
    {
        $active = Vacancy::factory()->active()->create(['parent_profile_id' => $this->parent->parentProfile->id]);
        $pending = Vacancy::factory()->create(['parent_profile_id' => $this->parent->parentProfile->id]);

        Sanctum::actingAs($this->nanny);
        $this->postJson('/api/v1/conversations', ['vacancy_id' => $pending->id])->assertUnprocessable();
        $this->postJson('/api/v1/conversations', ['vacancy_id' => $active->id])
            ->assertCreated()
            ->assertJsonPath('data.other.role', 'parent')
            ->assertJsonPath('data.vacancy.id', $active->id);
        $this->assertSame($active->id, Conversation::first()->vacancy_id);
    }

    public function test_cannot_chat_with_yourself(): void
    {
        $this->parent->assignRole('nanny');
        $this->parent->nannyProfile()->create(['region_id' => 1, 'status' => 'active']);

        Sanctum::actingAs($this->parent);
        $this->postJson('/api/v1/conversations', ['nanny_profile_id' => $this->parent->nannyProfile->id])->assertUnprocessable();
    }

    public function test_start_from_application_by_either_side(): void
    {
        $vacancy = Vacancy::factory()->active()->create(['parent_profile_id' => $this->parent->parentProfile->id]);
        $application = Application::create(['vacancy_id' => $vacancy->id, 'nanny_profile_id' => $this->nanny->nannyProfile->id, 'status' => 'sent']);

        Sanctum::actingAs($this->parent);
        $this->postJson('/api/v1/conversations', ['application_id' => $application->id])
            ->assertCreated()->assertJsonPath('data.other.id', $this->nanny->id);

        Sanctum::actingAs($this->nanny);
        $this->postJson('/api/v1/conversations', ['application_id' => $application->id])
            ->assertOk()->assertJsonPath('data.other.id', $this->parent->id);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/conversations', ['application_id' => $application->id])->assertForbidden();
    }

    public function test_cannot_start_when_other_blocked_you(): void
    {
        $c = $this->chat();
        $c->participants()->updateExistingPivot($this->nanny->id, ['blocked_at' => now()]);

        Sanctum::actingAs($this->parent);
        $this->postJson('/api/v1/conversations', ['nanny_profile_id' => $this->nanny->nannyProfile->id])->assertForbidden();
    }

    // --- Ro'yxat, o'qilmaganlar ---

    public function test_listing_sorted_with_unread_and_privacy(): void
    {
        $other = $this->nannyUser(['status' => 'active'], 'Sevara Aliyeva');
        $old = $this->chat();
        $new = Conversation::between($this->parent, $other);
        $this->say($old, $this->nanny, 'Birinchi');
        $this->say($old, $this->nanny, 'Ikkinchi');
        $this->say($new, $other, 'Yangi');
        $old->update(['last_message_at' => now()->subHour()]);
        Conversation::between($this->parent, User::factory()->create()); // xabarsiz — ko'rinmaydi

        Sanctum::actingAs($this->parent);
        $response = $this->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.0.unread_count', 1)
            ->assertJsonPath('data.1.unread_count', 2)
            ->assertJsonPath('data.1.last_message.preview', 'Ikkinchi')
            ->assertJsonPath('data.1.last_message.sender_is_me', false);

        $json = $response->getContent();
        $this->assertStringNotContainsString($this->nanny->phone, $json);
        $this->assertStringNotContainsString($this->nanny->telegram_username, $json);
        $this->assertStringNotContainsString('telegram', $json);
        $this->assertStringNotContainsString('phone', $json);

        $this->getJson('/api/v1/chat/unread')->assertOk()->assertJsonPath('total', 3);
        $this->getJson("/api/v1/conversations/{$old->id}")->assertOk()->assertJsonPath('data.id', $old->id);
    }

    public function test_outsider_cannot_see_conversation(): void
    {
        $c = $this->chat();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/conversations/{$c->id}")->assertForbidden();
        $this->getJson("/api/v1/conversations/{$c->id}/messages")->assertForbidden();
        $this->postJson("/api/v1/conversations/{$c->id}/messages", ['type' => 'text', 'body' => 'x'])->assertForbidden();
    }

    // --- Xabarlar ---

    public function test_messages_pagination_before_id(): void
    {
        $c = $this->chat();
        foreach (range(1, 5) as $i) {
            $this->say($c, $this->nanny, "Xabar {$i}");
        }
        $ids = $c->messages()->pluck('id');

        Sanctum::actingAs($this->parent);
        $page = $this->getJson("/api/v1/conversations/{$c->id}/messages?limit=3")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonPath('data.0.body', 'Xabar 3')
            ->assertJsonPath('data.2.body', 'Xabar 5')
            ->assertJsonPath('data.0.is_mine', false);

        $this->getJson("/api/v1/conversations/{$c->id}/messages?limit=3&before_id={$page->json('data.0.id')}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.has_more', false)
            ->assertJsonPath('data.0.id', $ids[0]);

        $this->getJson("/api/v1/conversations/{$c->id}/messages?limit=51")->assertUnprocessable();
    }

    public function test_send_text_broadcasts_and_updates_recipient(): void
    {
        $c = $this->chat();
        Sanctum::actingAs($this->parent);

        $this->postJson("/api/v1/conversations/{$c->id}/messages", ['type' => 'text', 'body' => 'Assalomu alaykum'])
            ->assertCreated()
            ->assertJsonPath('data.is_mine', true)
            ->assertJsonPath('data.body', 'Assalomu alaykum')
            ->assertJsonPath('data.read', false)
            ->assertJsonPath('data.deleted', false);

        Event::assertDispatched(MessageSent::class, function (MessageSent $e) use ($c) {
            $payload = $e->broadcastWith();

            return $e->broadcastAs() === 'message.sent'
                && $e->broadcastOn()[0]->name === 'private-conversation.'.$c->id
                && $payload['sender_id'] === $this->parent->id
                && ! array_key_exists('is_mine', $payload);
        });
        Event::assertDispatched(ConversationUpdated::class, fn (ConversationUpdated $e) => $e->userId === $this->nanny->id
            && $e->payload['unread_count'] === 1
            && $e->payload['last_message']['preview'] === 'Assalomu alaykum');
        Event::assertNotDispatched(ConversationUpdated::class, fn ($e) => $e->userId === $this->parent->id);
    }

    public function test_text_requires_body_and_limits_length(): void
    {
        $c = $this->chat();
        Sanctum::actingAs($this->parent);

        $this->postJson("/api/v1/conversations/{$c->id}/messages", ['type' => 'text'])->assertUnprocessable();
        $this->postJson("/api/v1/conversations/{$c->id}/messages", ['type' => 'text', 'body' => str_repeat('a', 4001)])->assertUnprocessable();
        $this->postJson("/api/v1/conversations/{$c->id}/messages", ['type' => 'bad', 'body' => 'x'])->assertUnprocessable();
    }

    public function test_send_image_and_voice(): void
    {
        $c = $this->chat();
        Sanctum::actingAs($this->parent);

        $image = $this->post("/api/v1/conversations/{$c->id}/messages", [
            'type' => 'image', 'file' => UploadedFile::fake()->image('a.jpg', 100, 100),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.type', 'image');
        $this->assertStringContainsString('chat-files/'.$image->json('data.id'), $image->json('data.attachment.url'));
        $this->assertStringContainsString('signature=', $image->json('data.attachment.url'));
        $this->assertNotNull($image->json('data.attachment.mime'));
        Storage::disk(Message::DISK)->assertExists(Message::find($image->json('data.id'))->attachment_path);
        $this->assertStringStartsWith("chat/{$c->id}/", Message::find($image->json('data.id'))->attachment_path);

        $voice = $this->post("/api/v1/conversations/{$c->id}/messages", [
            'type' => 'voice', 'duration' => 12, 'file' => UploadedFile::fake()->create('v.webm', 50, 'audio/webm'),
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.type', 'voice')
            ->assertJsonPath('data.attachment.duration', 12);
        $this->assertSame('🎤 Ovozli xabar', Message::find($voice->json('data.id'))->preview());
    }

    public function test_file_validation(): void
    {
        $c = $this->chat();
        Sanctum::actingAs($this->parent);
        $h = ['Accept' => 'application/json'];

        $this->post("/api/v1/conversations/{$c->id}/messages", ['type' => 'image'], $h)->assertUnprocessable();
        $this->post("/api/v1/conversations/{$c->id}/messages", ['type' => 'image', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], $h)->assertUnprocessable();
        $this->post("/api/v1/conversations/{$c->id}/messages", ['type' => 'image', 'file' => UploadedFile::fake()->image('big.jpg')->size(10241)], $h)->assertUnprocessable();
        $this->post("/api/v1/conversations/{$c->id}/messages", ['type' => 'voice', 'file' => UploadedFile::fake()->create('v.webm', 5121, 'audio/webm')], $h)->assertUnprocessable();
        $this->post("/api/v1/conversations/{$c->id}/messages", ['type' => 'voice', 'duration' => 181, 'file' => UploadedFile::fake()->create('v.webm', 10, 'audio/webm')], $h)->assertUnprocessable();
    }

    public function test_reply_must_be_in_same_conversation(): void
    {
        $c = $this->chat();
        $mine = $this->say($c, $this->nanny, 'Savol');
        $foreign = $this->say(Conversation::between($this->parent, User::factory()->create()), $this->parent, 'Boshqa');

        Sanctum::actingAs($this->parent);
        $this->postJson("/api/v1/conversations/{$c->id}/messages", ['type' => 'text', 'body' => 'Javob', 'reply_to_id' => $foreign->id])->assertUnprocessable();
        $this->postJson("/api/v1/conversations/{$c->id}/messages", ['type' => 'text', 'body' => 'Javob', 'reply_to_id' => $mine->id])
            ->assertCreated()
            ->assertJsonPath('data.reply_to.id', $mine->id)
            ->assertJsonPath('data.reply_to.preview', 'Savol')
            ->assertJsonPath('data.reply_to.sender_id', $this->nanny->id);
    }

    public function test_contact_warning_only_early_in_conversation(): void
    {
        $c = $this->chat();
        Sanctum::actingAs($this->parent);
        $url = "/api/v1/conversations/{$c->id}/messages";

        foreach (['+998 90 123 45 67 ga qo\'ng\'iroq qiling', 'https://example.com ga kiring', 't.me/someone', 'menga @dilnoza_k yozing'] as $text) {
            $this->postJson($url, ['type' => 'text', 'body' => $text])->assertCreated()->assertJsonPath('data.meta.contact_warning', true);
        }
        $this->postJson($url, ['type' => 'text', 'body' => 'Soat 9 dan 18 gacha, 2 yoshli bola'])
            ->assertCreated()->assertJsonMissingPath('data.meta.contact_warning');

        foreach (range(1, 6) as $i) {
            $this->say($c, $this->nanny);
        }
        $this->postJson($url, ['type' => 'text', 'body' => '+998901234567'])
            ->assertCreated()->assertJsonMissingPath('data.meta.contact_warning');
    }

    public function test_edit_within_window_only_own_text(): void
    {
        $c = $this->chat();
        $message = $this->say($c, $this->parent, 'Eski');
        $theirs = $this->say($c, $this->nanny);

        Sanctum::actingAs($this->parent);
        $this->patchJson("/api/v1/messages/{$message->id}", ['body' => 'Yangi'])
            ->assertOk()->assertJsonPath('data.body', 'Yangi');
        $this->assertNotNull($message->fresh()->edited_at);
        Event::assertDispatched(MessageUpdated::class, fn ($e) => $e->broadcastAs() === 'message.updated');

        $this->patchJson("/api/v1/messages/{$theirs->id}", ['body' => 'Hack'])->assertForbidden();

        $message->forceFill(['created_at' => now()->subHours(25)])->save();
        $this->patchJson("/api/v1/messages/{$message->id}", ['body' => 'Kech'])->assertUnprocessable();
    }

    public function test_delete_hides_content_and_removes_file(): void
    {
        $c = $this->chat();
        Sanctum::actingAs($this->parent);
        $sent = $this->post("/api/v1/conversations/{$c->id}/messages", [
            'type' => 'image', 'body' => 'rasm', 'file' => UploadedFile::fake()->image('a.png'),
        ], ['Accept' => 'application/json'])->assertCreated();
        $message = Message::find($sent->json('data.id'));
        $path = $message->attachment_path;

        $this->deleteJson("/api/v1/messages/{$message->id}")->assertOk();

        Storage::disk(Message::DISK)->assertMissing($path);
        Event::assertDispatched(MessageDeleted::class, fn ($e) => $e->broadcastAs() === 'message.deleted'
            && $e->broadcastWith()['deleted'] === true && $e->broadcastWith()['body'] === null);

        $this->getJson("/api/v1/conversations/{$c->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.deleted', true)
            ->assertJsonPath('data.0.body', null)
            ->assertJsonPath('data.0.attachment', null);

        Sanctum::actingAs($this->nanny);
        $other = $this->say($c, $this->parent);
        $this->deleteJson("/api/v1/messages/{$other->id}")->assertForbidden();
    }

    // --- O'qildi, mute, block, report ---

    public function test_read_receipts_only_increase(): void
    {
        $c = $this->chat();
        $first = $this->say($c, $this->parent, '1');
        $second = $this->say($c, $this->parent, '2');

        Sanctum::actingAs($this->nanny);
        $this->postJson("/api/v1/conversations/{$c->id}/read", ['message_id' => $second->id])
            ->assertOk()->assertJsonPath('data.unread_count', 0);
        $this->postJson("/api/v1/conversations/{$c->id}/read", ['message_id' => $first->id])
            ->assertOk()->assertJsonPath('data.last_read_message_id', $second->id);

        Event::assertDispatched(ConversationRead::class, fn ($e) => $e->broadcastWith() === [
            'conversation_id' => $c->id, 'user_id' => $this->nanny->id, 'last_read_message_id' => $second->id,
        ]);
        Event::assertDispatchedTimes(ConversationRead::class, 1);
        Event::assertDispatched(ConversationUpdated::class, fn ($e) => $e->userId === $this->nanny->id && $e->payload['unread_count'] === 0);

        $this->postJson("/api/v1/conversations/{$c->id}/read", ['message_id' => 99999])->assertUnprocessable();

        Sanctum::actingAs($this->parent);
        $this->getJson("/api/v1/conversations/{$c->id}/messages")
            ->assertJsonPath('data.0.read', true)
            ->assertJsonPath('data.1.read', true);
    }

    public function test_mute(): void
    {
        $c = $this->chat();
        Sanctum::actingAs($this->parent);

        $this->postJson("/api/v1/conversations/{$c->id}/mute", ['muted' => true])->assertOk()->assertJsonPath('data.muted', true);
        $this->postJson("/api/v1/conversations/{$c->id}/mute", ['muted' => false])->assertOk()->assertJsonPath('data.muted', false);
        $this->postJson("/api/v1/conversations/{$c->id}/mute", [])->assertUnprocessable();
    }

    public function test_block_prevents_sending_both_ways_and_can_be_undone(): void
    {
        $c = $this->chat();
        $url = "/api/v1/conversations/{$c->id}/messages";

        Sanctum::actingAs($this->parent);
        $this->postJson("/api/v1/conversations/{$c->id}/block")->assertOk()->assertJsonPath('data.blocked_by_me', true);
        $this->postJson($url, ['type' => 'text', 'body' => 'x'])->assertForbidden();

        Sanctum::actingAs($this->nanny);
        $this->getJson("/api/v1/conversations/{$c->id}")->assertJsonPath('data.blocked_me', true);
        $this->postJson($url, ['type' => 'text', 'body' => 'x'])->assertForbidden();

        Sanctum::actingAs($this->parent);
        $this->deleteJson("/api/v1/conversations/{$c->id}/block")->assertOk()->assertJsonPath('data.blocked_by_me', false);
        $this->postJson($url, ['type' => 'text', 'body' => 'x'])->assertCreated();
    }

    public function test_report_conversation(): void
    {
        $c = $this->chat();
        Sanctum::actingAs($this->parent);

        $this->postJson("/api/v1/conversations/{$c->id}/report", ['reason' => 'nope'])->assertUnprocessable();
        $this->postJson("/api/v1/conversations/{$c->id}/report", ['reason' => 'fraud', 'comment' => 'Pul so\'radi'])
            ->assertCreated()->assertJsonPath('data.status', 'open');

        $report = Report::first();
        $this->assertSame($c->id, $report->reportable->id);
        $this->assertSame($this->parent->id, $report->reporter_id);
        $this->assertSame(ReportStatus::Open, $report->status);
    }

    // --- Fayl, kanal, last_seen ---

    public function test_signed_file_route(): void
    {
        $c = $this->chat();
        Storage::disk(Message::DISK)->put("chat/{$c->id}/a.jpg", 'IMG');
        $message = $c->messages()->create([
            'sender_id' => $this->parent->id, 'type' => 'image', 'attachment_path' => "chat/{$c->id}/a.jpg", 'meta' => ['mime' => 'image/jpeg'],
        ]);

        $signed = URL::temporarySignedRoute('chat-files', now()->addMinutes(5), ['message' => $message->id]);
        $this->get($signed)->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control');
        $this->assertStringContainsString('private', $this->get($signed)->headers->get('Cache-Control'));

        $this->get("/api/v1/chat-files/{$message->id}")->assertForbidden();
        $this->get(URL::temporarySignedRoute('chat-files', now()->subMinute(), ['message' => $message->id]))->assertForbidden();
    }

    public function test_channel_authorization_via_broadcast_auth(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'k',
            'broadcasting.connections.reverb.secret' => 's',
            'broadcasting.connections.reverb.app_id' => '1',
        ]);
        Broadcast::purge();
        require base_path('routes/channels.php');

        $c = $this->chat();
        $outsider = User::factory()->create();
        $auth = fn (string $channel) => $this->postJson('/api/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => $channel]);

        Sanctum::actingAs($this->parent);
        $auth("private-conversation.{$c->id}")->assertOk()->assertJsonStructure(['auth']);
        $auth("private-user.{$this->parent->id}")->assertOk();
        $auth("private-user.{$this->nanny->id}")->assertForbidden();
        $auth('presence-online')->assertOk()->assertJsonStructure(['auth', 'channel_data']);

        Sanctum::actingAs($outsider);
        $auth("private-conversation.{$c->id}")->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/broadcasting/auth', ['socket_id' => '1.2', 'channel_name' => "private-conversation.{$c->id}"])->assertUnauthorized();
    }

    public function test_last_seen_updated_at_most_once_a_minute(): void
    {
        Sanctum::actingAs($this->parent);
        $this->getJson('/api/v1/chat/unread');
        $first = $this->parent->fresh()->last_seen_at;
        $this->assertNotNull($first);

        $this->travel(30)->seconds();
        $this->getJson('/api/v1/chat/unread');
        $this->assertTrue($first->equalTo($this->parent->fresh()->last_seen_at));

        $this->travel(61)->seconds();
        $this->getJson('/api/v1/chat/unread');
        $this->assertTrue($this->parent->fresh()->last_seen_at->greaterThan($first));
    }

    // --- Telegram ---

    public function test_telegram_notification_dispatched_and_throttled(): void
    {
        Queue::fake();
        Cache::flush();
        config(['services.telegram.bot_token' => 'token']);
        $this->nanny->update(['telegram_id' => 555]);
        $c = $this->chat();

        Sanctum::actingAs($this->parent);
        $url = "/api/v1/conversations/{$c->id}/messages";
        $this->postJson($url, ['type' => 'text', 'body' => 'Bir'])->assertCreated();
        $this->postJson($url, ['type' => 'text', 'body' => 'Ikki'])->assertCreated();

        Queue::assertPushed(SendChatTelegramNotification::class, 1);

        $this->travel(11)->minutes();
        $this->postJson($url, ['type' => 'text', 'body' => 'Uch'])->assertCreated();
        Queue::assertPushed(SendChatTelegramNotification::class, 2);
    }

    public function test_telegram_notification_skipped_when_muted_no_token_or_no_telegram(): void
    {
        Queue::fake();
        Cache::flush();
        $c = $this->chat();
        Sanctum::actingAs($this->parent);
        $url = "/api/v1/conversations/{$c->id}/messages";

        // Token yo'q
        config(['services.telegram.bot_token' => '']);
        $this->nanny->update(['telegram_id' => 555]);
        $this->postJson($url, ['type' => 'text', 'body' => 'a'])->assertCreated();

        config(['services.telegram.bot_token' => 'token']);
        // Ovozi o'chirilgan
        $c->participants()->updateExistingPivot($this->nanny->id, ['muted' => true]);
        $this->postJson($url, ['type' => 'text', 'body' => 'b'])->assertCreated();

        // Telegram ulanmagan
        $c->participants()->updateExistingPivot($this->nanny->id, ['muted' => false]);
        $this->nanny->update(['telegram_id' => null]);
        $this->postJson($url, ['type' => 'text', 'body' => 'c'])->assertCreated();

        Queue::assertNothingPushed();
    }

    public function test_telegram_job_sends_localized_message(): void
    {
        Http::fake();
        config(['services.telegram.bot_token' => 'token', 'app.frontend_url' => 'https://enagalar.uz']);
        $this->nanny->update(['telegram_id' => 555, 'locale' => 'ru']);
        $c = $this->chat();
        $message = $this->say($c, $this->parent, 'Salom <b>');

        (new SendChatTelegramNotification($message->id, $this->nanny->id))->handle(app(TelegramBot::class));

        Http::assertSent(function ($request) use ($c) {
            return str_contains($request->url(), '/sendMessage')
                && $request['chat_id'] === 555
                && str_contains($request['text'], '💬 Новое сообщение: Aziza R.')
                && str_contains($request['text'], 'Salom &lt;b&gt;')
                && str_contains($request['text'], "https://enagalar.uz/kabinet/xabarlar/{$c->id}");
        });
    }
}
