<?php

namespace Tests\Feature;

use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_add_list_and_remove_favorites(): void
    {
        $nanny = $this->nannyUser(['status' => 'active'])->nannyProfile;
        $vacancy = Vacancy::factory()->active()->create(['parent_profile_id' => $this->parentUser()->parentProfile->id]);
        Sanctum::actingAs($this->parentUser());

        $this->postJson('/api/v1/favorites', ['type' => 'nanny', 'id' => $nanny->id])->assertCreated();
        $this->postJson('/api/v1/favorites', ['type' => 'nanny', 'id' => $nanny->id])->assertOk(); // takror — dublikat yo'q
        $this->postJson('/api/v1/favorites', ['type' => 'vacancy', 'id' => $vacancy->id])->assertCreated();

        $response = $this->getJson('/api/v1/favorites')->assertOk()->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing(['nanny', 'vacancy'], collect($response->json('data'))->pluck('type')->all());
        $this->assertStringNotContainsString('998907778899', $response->getContent());

        $this->deleteJson('/api/v1/favorites', ['type' => 'nanny', 'id' => $nanny->id])->assertNoContent();
        $this->getJson('/api/v1/favorites')->assertJsonCount(1, 'data');
    }

    public function test_cannot_favorite_inactive_or_invalid(): void
    {
        $pending = $this->nannyUser(['status' => 'pending'])->nannyProfile;
        Sanctum::actingAs($this->parentUser());

        $this->postJson('/api/v1/favorites', ['type' => 'nanny', 'id' => $pending->id])->assertNotFound();
        $this->postJson('/api/v1/favorites', ['type' => 'user', 'id' => 1])->assertUnprocessable();
    }

    public function test_favorites_are_private_and_need_auth(): void
    {
        $nanny = $this->nannyUser(['status' => 'active'])->nannyProfile;
        $a = $this->parentUser();
        $a->favorites()->create(['favoritable_type' => 'nanny', 'favoritable_id' => $nanny->id]);

        $this->getJson('/api/v1/favorites')->assertUnauthorized();
        Sanctum::actingAs($this->parentUser());
        $this->getJson('/api/v1/favorites')->assertJsonCount(0, 'data');
    }
}
