<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_between_reuses_existing_conversation(): void
    {
        [$parent, $nanny, $other] = User::factory()->count(3)->create();

        $first = Conversation::between($parent, $nanny);
        $again = Conversation::between($nanny, $parent);
        $different = Conversation::between($parent, $other);

        $this->assertTrue($first->is($again));
        $this->assertFalse($first->is($different));
        $this->assertCount(2, $first->participants);
    }

    public function test_new_message_updates_last_message_at(): void
    {
        [$a, $b] = User::factory()->count(2)->create();
        $conversation = Conversation::between($a, $b);

        $conversation->messages()->create(['sender_id' => $a->id, 'body' => 'Assalomu alaykum!']);

        $this->assertNotNull($conversation->fresh()->last_message_at);
    }
}
