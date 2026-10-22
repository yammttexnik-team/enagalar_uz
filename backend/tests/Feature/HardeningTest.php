<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\NannyProfile;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
        RateLimiter::clear('x');
    }

    public function test_public_search_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $this->getJson('/api/v1/nannies')->assertOk();
        }

        $this->getJson('/api/v1/nannies')->assertStatus(429);
        $this->getJson('/api/v1/vacancies')->assertStatus(429);
    }

    public function test_favorites_and_conversation_start_are_rate_limited(): void
    {
        Sanctum::actingAs($this->parentUser());

        for ($i = 0; $i < 60; $i++) {
            $this->deleteJson('/api/v1/favorites', ['type' => 'nanny', 'id' => 1])->assertNoContent();
        }
        $this->deleteJson('/api/v1/favorites', ['type' => 'nanny', 'id' => 1])->assertStatus(429);

        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/v1/conversations', [])->assertUnprocessable();
        }
        $this->postJson('/api/v1/conversations', [])->assertStatus(429);
    }

    public function test_nanny_view_counted_once_per_viewer_and_not_for_owner(): void
    {
        $owner = $this->nannyUser(['status' => 'active']);
        $profile = $owner->nannyProfile;

        $this->getJson("/api/v1/nannies/{$profile->id}")->assertOk();
        $this->getJson("/api/v1/nannies/{$profile->id}")->assertOk();
        $this->assertSame(1, $profile->fresh()->views_count);

        // Boshqa IP — yangi ko'ruvchi.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])->getJson("/api/v1/nannies/{$profile->id}")->assertOk();
        $this->assertSame(2, $profile->fresh()->views_count);

        // Egasi hisobga olinmaydi.
        Sanctum::actingAs($owner);
        $this->getJson("/api/v1/nannies/{$profile->id}")->assertOk();
        $this->assertSame(2, $profile->fresh()->views_count);

        // Kirgan boshqa foydalanuvchi bir marta.
        Sanctum::actingAs($this->parentUser());
        $this->getJson("/api/v1/nannies/{$profile->id}")->assertOk();
        $this->getJson("/api/v1/nannies/{$profile->id}")->assertOk();
        $this->assertSame(3, $profile->fresh()->views_count);
    }

    public function test_vacancy_view_counted_once_and_not_for_owner(): void
    {
        $owner = $this->parentUser();
        $vacancy = Vacancy::factory()->active()->create(['parent_profile_id' => $owner->parentProfile->id]);

        $this->getJson("/api/v1/vacancies/{$vacancy->id}")->assertOk();
        $this->getJson("/api/v1/vacancies/{$vacancy->id}")->assertOk();
        $this->assertSame(1, $vacancy->fresh()->views_count);

        Sanctum::actingAs($owner);
        $this->getJson("/api/v1/vacancies/{$vacancy->id}")->assertOk();
        $this->assertSame(1, $vacancy->fresh()->views_count);
    }

    public function test_between_sets_pair_key_and_recovers_from_duplicate(): void
    {
        [$a, $b] = User::factory()->count(2)->create();

        $first = Conversation::between($b, $a);
        $this->assertSame(min($a->id, $b->id).':'.max($a->id, $b->id), $first->pair_key);
        $this->assertTrue(Conversation::between($a, $b)->is($first));
        $this->assertSame(1, Conversation::count());

        // Unikal kalit dublikatga yo'l qo'ymaydi.
        $this->expectException(UniqueConstraintViolationException::class);
        Conversation::create(['pair_key' => $first->pair_key]);
    }

    public function test_favorites_are_paginated_in_db_and_skip_inactive(): void
    {
        $user = $this->parentUser();
        $nannies = collect(range(1, 3))->map(fn () => $this->nannyUser(['status' => 'active'])->nannyProfile);
        $hidden = $this->nannyUser(['status' => 'draft'])->nannyProfile;
        Sanctum::actingAs($user);

        foreach ($nannies as $n) {
            $user->favorites()->create(['favoritable_type' => 'nanny', 'favoritable_id' => $n->id]);
        }
        $user->favorites()->create(['favoritable_type' => 'nanny', 'favoritable_id' => $hidden->id]);

        $this->getJson('/api/v1/favorites')->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [['type', 'item' => ['id']]]]);

        $this->assertInstanceOf(NannyProfile::class, $nannies->first());
    }

    public function test_show_own_vacancy(): void
    {
        $owner = $this->parentUser();
        $mine = Vacancy::factory()->create(['parent_profile_id' => $owner->parentProfile->id, 'status' => 'pending']);
        $other = Vacancy::factory()->create(['parent_profile_id' => $this->parentUser()->parentProfile->id]);

        $this->getJson("/api/v1/my/vacancies/{$mine->id}")->assertUnauthorized();

        Sanctum::actingAs($owner);
        $this->getJson("/api/v1/my/vacancies/{$mine->id}")->assertOk()
            ->assertJsonPath('data.id', $mine->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.applications_count', 0)
            ->assertJsonStructure(['data' => ['id', 'status', 'region_id', 'district_id', 'views_count', 'applications_count']]);
        $this->getJson("/api/v1/my/vacancies/{$other->id}")->assertForbidden();
        $this->getJson('/api/v1/my/vacancies/999999')->assertNotFound();
    }
}
