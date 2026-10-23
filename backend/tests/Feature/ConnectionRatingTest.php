<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Connection;
use App\Models\Conversation;
use App\Models\Review;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class ConnectionRatingTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    private User $parent;

    private User $nanny;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Event::fake([MessageSent::class, ConversationUpdated::class]);

        $this->parent = $this->parentUser();
        $this->nanny = $this->nannyUser(['status' => 'active']);
    }

    private function send(Conversation $c, User $from, string $body = 'Salom'): void
    {
        Sanctum::actingAs($from);
        $this->postJson("/api/v1/conversations/{$c->id}/messages", ['type' => 'text', 'body' => $body])->assertCreated();
    }

    /** Ikkala tomon yozgan, so'ralgan bog'lanish. */
    private function askedConnection(?bool $parentWorked = null, ?bool $nannyWorked = null): Connection
    {
        $c = Conversation::between($this->parent, $this->nanny);

        return Connection::create([
            'conversation_id' => $c->id,
            'parent_id' => $this->parent->id,
            'nanny_id' => $this->nanny->id,
            'connected_at' => now()->subDays(4),
            'asked_at' => now()->subDay(),
            'parent_worked' => $parentWorked,
            'nanny_worked' => $nannyWorked,
        ]);
    }

    // --- Bog'lanishni aniqlash ---

    public function test_connection_recorded_only_when_both_sides_wrote(): void
    {
        $c = Conversation::between($this->parent, $this->nanny);

        $this->send($c, $this->parent);
        $this->send($c, $this->parent, 'Yana');
        $this->assertSame(0, Connection::count());

        $this->send($c, $this->nanny, 'Assalomu alaykum');
        $this->send($c, $this->nanny, 'Ha');

        $this->assertSame(1, Connection::count());
        $connection = Connection::first();
        $this->assertSame($this->parent->id, $connection->parent_id);
        $this->assertSame($this->nanny->id, $connection->nanny_id);
        $this->assertNotNull($connection->connected_at);
        $this->assertNull($connection->asked_at);
    }

    public function test_two_nannies_do_not_form_connection(): void
    {
        $other = $this->nannyUser(['status' => 'active'], 'Malika Tosheva');
        $c = Conversation::between($this->nanny, $other);

        $this->send($c, $this->nanny);
        $this->send($c, $other);

        $this->assertSame(0, Connection::count());
    }

    // --- Rejalashtirilgan savol ---

    public function test_scheduler_asks_both_once_after_three_days(): void
    {
        Http::fake();
        config(['services.telegram.bot_token' => 'token', 'app.frontend_url' => 'https://enagalar.uz']);
        $this->parent->update(['telegram_id' => 111]);
        $this->nanny->update(['telegram_id' => 222, 'locale' => 'ru']);

        $c = Conversation::between($this->parent, $this->nanny);
        $fresh = Connection::create([
            'conversation_id' => $c->id, 'parent_id' => $this->parent->id, 'nanny_id' => $this->nanny->id,
            'connected_at' => now()->subDays(2),
        ]);

        $this->artisan('connections:ask')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertNull($fresh->fresh()->asked_at);

        $this->travel(2)->days();
        $this->artisan('connections:ask')->assertSuccessful();

        $this->assertNotNull($fresh->fresh()->asked_at);
        Http::assertSentCount(2);
        Http::assertSent(fn ($r) => $r['chat_id'] === 111
            && str_contains($r['text'], 'Birga ishladingizmi?')
            && str_contains($r['text'], 'Dilnoza K.')
            && $r['reply_markup']['inline_keyboard'][0][0]['url'] === 'https://enagalar.uz/kabinet/baholash');
        Http::assertSent(fn ($r) => $r['chat_id'] === 222 && str_contains($r['text'], 'Вы работали вместе?'));

        // Ikkinchi marta so'ralmaydi
        $this->artisan('connections:ask')->assertSuccessful();
        Http::assertSentCount(2);
    }

    public function test_schedule_registered(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('connections:ask')->assertSuccessful();
    }

    // --- Javob ---

    public function test_answer_once_and_list_status(): void
    {
        $connection = $this->askedConnection();

        Sanctum::actingAs($this->parent);
        $this->getJson('/api/v1/my/connections')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'question')
            ->assertJsonPath('data.0.my_role', 'parent')
            ->assertJsonPath('data.0.other.name', 'Dilnoza K.')
            ->assertJsonPath('data.0.other.nanny_profile_id', $this->nanny->nannyProfile->id)
            ->assertJsonPath('meta.pending_count', 1);

        $this->postJson("/api/v1/connections/{$connection->id}/answer", ['worked' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'waiting')
            ->assertJsonPath('data.my_answer', true);

        $this->postJson("/api/v1/connections/{$connection->id}/answer", ['worked' => false])->assertUnprocessable();
        $this->assertTrue($connection->fresh()->parent_worked);

        Sanctum::actingAs($this->nanny);
        $this->postJson("/api/v1/connections/{$connection->id}/answer", ['worked' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'can_review');
    }

    public function test_not_asked_connection_is_hidden_and_cannot_be_answered(): void
    {
        $connection = $this->askedConnection();
        $connection->update(['asked_at' => null]);

        Sanctum::actingAs($this->parent);
        $this->getJson('/api/v1/my/connections')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson("/api/v1/connections/{$connection->id}/answer", ['worked' => true])->assertUnprocessable();
    }

    public function test_stranger_cannot_answer_or_review(): void
    {
        $connection = $this->askedConnection(true, true);
        $stranger = User::factory()->create()->assignRole(Role::Parent->value);

        Sanctum::actingAs($stranger);
        $this->postJson("/api/v1/connections/{$connection->id}/answer", ['worked' => true])->assertForbidden();
        $this->postJson("/api/v1/connections/{$connection->id}/review", ['rating' => 5])->assertForbidden();
        $this->getJson('/api/v1/my/connections')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_guest_cannot_access(): void
    {
        $connection = $this->askedConnection(true, true);

        $this->getJson('/api/v1/my/connections')->assertUnauthorized();
        $this->postJson("/api/v1/connections/{$connection->id}/review", ['rating' => 5])->assertUnauthorized();
    }

    // --- Baholash ---

    public function test_review_requires_both_yes(): void
    {
        $connection = $this->askedConnection(true, false);

        Sanctum::actingAs($this->parent);
        $this->postJson("/api/v1/connections/{$connection->id}/review", ['rating' => 5])->assertUnprocessable();

        $connection->update(['nanny_worked' => null]);
        $this->postJson("/api/v1/connections/{$connection->id}/review", ['rating' => 5])->assertUnprocessable();

        $this->assertSame(0, Review::count());
    }

    public function test_each_side_reviews_once_and_ratings_recalculated(): void
    {
        $connection = $this->askedConnection(true, true);

        Sanctum::actingAs($this->parent);
        $this->postJson("/api/v1/connections/{$connection->id}/review", ['rating' => 4, 'comment' => 'Juda mehribon'])
            ->assertCreated()
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.author', 'Aziza R.');

        $this->postJson("/api/v1/connections/{$connection->id}/review", ['rating' => 1])->assertUnprocessable();
        $this->postJson("/api/v1/connections/{$connection->id}/review", ['rating' => 6])->assertUnprocessable();

        Sanctum::actingAs($this->nanny);
        $this->postJson("/api/v1/connections/{$connection->id}/review", ['rating' => 5])->assertCreated();

        $nannyProfile = $this->nanny->nannyProfile->fresh();
        $this->assertSame('4.00', (string) $nannyProfile->rating_avg);
        $this->assertSame(1, $nannyProfile->reviews_count);

        $parentProfile = $this->parent->parentProfile->fresh();
        $this->assertSame('5.00', (string) $parentProfile->rating_avg);
        $this->assertSame(1, $parentProfile->reviews_count);

        $this->assertSame(2, Review::count());
        $this->getJson('/api/v1/my/connections')->assertJsonPath('data.0.status', 'reviewed')
            ->assertJsonPath('data.0.my_review.rating', 5);

        // Ochiq anketa va sharhlar ro'yxati
        $id = $this->nanny->nannyProfile->id;
        $this->getJson("/api/v1/nannies/{$id}")->assertJsonPath('data.rating', 4)->assertJsonPath('data.reviews_count', 1);
        $this->getJson("/api/v1/nannies/{$id}/reviews")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.comment', 'Juda mehribon')
            ->assertJsonPath('data.0.author', 'Aziza R.')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_average_over_several_reviews(): void
    {
        $other = $this->parentUser();
        $c = Conversation::between($other, $this->nanny);
        $second = Connection::create([
            'conversation_id' => $c->id, 'parent_id' => $other->id, 'nanny_id' => $this->nanny->id,
            'connected_at' => now()->subDays(5), 'asked_at' => now(), 'parent_worked' => true, 'nanny_worked' => true,
        ]);
        $first = $this->askedConnection(true, true);

        Sanctum::actingAs($this->parent);
        $this->postJson("/api/v1/connections/{$first->id}/review", ['rating' => 5])->assertCreated();
        Sanctum::actingAs($other);
        $this->postJson("/api/v1/connections/{$second->id}/review", ['rating' => 2])->assertCreated();

        $profile = $this->nanny->nannyProfile->fresh();
        $this->assertSame('3.50', (string) $profile->rating_avg);
        $this->assertSame(2, $profile->reviews_count);
    }

    public function test_contacts_stripped_from_comment(): void
    {
        $connection = $this->askedConnection(true, true);

        Sanctum::actingAs($this->parent);
        $this->postJson("/api/v1/connections/{$connection->id}/review", [
            'rating' => 5,
            'comment' => 'Zo\'r enaga, +998 90 123 45 67 ga yoki @dilnoza_k ga yozing, t.me/dilnoza',
        ])->assertCreated();

        $text = Review::first()->text;
        $this->assertStringNotContainsString('123 45 67', $text);
        $this->assertStringNotContainsString('@dilnoza_k', $text);
        $this->assertStringNotContainsString('t.me/dilnoza', $text);
        $this->assertStringContainsString('Zo\'r enaga', $text);
    }

    public function test_comment_too_long_rejected(): void
    {
        $connection = $this->askedConnection(true, true);

        Sanctum::actingAs($this->parent);
        $this->postJson("/api/v1/connections/{$connection->id}/review", ['rating' => 5, 'comment' => str_repeat('a', 1001)])
            ->assertUnprocessable();
    }

    // --- Moderatsiya ---

    public function test_hidden_review_excluded_and_rating_recalculated(): void
    {
        $connection = $this->askedConnection(true, true);
        Sanctum::actingAs($this->parent);
        $this->postJson("/api/v1/connections/{$connection->id}/review", ['rating' => 3, 'comment' => 'Yomon emas'])->assertCreated();
        $review = Review::first();

        $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
        $this->actingAs($moderator, 'web');
        $this->get('/admin/reviews')->assertOk()->assertSee('Yomon emas');

        Livewire::test(ListReviews::class)->callTableAction('hide', $review)->assertHasNoTableActionErrors();
        $this->assertSame(Review::HIDDEN, $review->fresh()->status);

        $profile = $this->nanny->nannyProfile->fresh();
        $this->assertSame(0, $profile->reviews_count);
        $this->assertSame('0.00', (string) $profile->rating_avg);
        $this->getJson("/api/v1/nannies/{$profile->id}/reviews")->assertOk()->assertJsonCount(0, 'data');

        Livewire::test(ListReviews::class)->callTableAction('unhide', $review)->assertHasNoTableActionErrors();
        $this->assertSame(1, $this->nanny->nannyProfile->fresh()->reviews_count);
    }

    public function test_parent_rating_visible_on_vacancy_and_conversation(): void
    {
        $this->parent->parentProfile->forceFill(['rating_avg' => 4.5, 'reviews_count' => 2])->save();
        Sanctum::actingAs($this->parent);
        $vacancyId = $this->postJson('/api/v1/my/vacancies', $this->vacancyPayload())->json('data.id');
        Vacancy::find($vacancyId)->publish();

        $this->getJson("/api/v1/vacancies/{$vacancyId}")
            ->assertOk()
            ->assertJsonPath('data.parent.rating', 4.5)
            ->assertJsonPath('data.parent.reviews_count', 2);

        $c = Conversation::between($this->parent, $this->nanny);
        Sanctum::actingAs($this->nanny);
        $this->getJson("/api/v1/conversations/{$c->id}")
            ->assertOk()
            ->assertJsonPath('data.other.role', 'parent')
            ->assertJsonPath('data.other.rating', 4.5)
            ->assertJsonPath('data.other.reviews_count', 2);
    }
}
