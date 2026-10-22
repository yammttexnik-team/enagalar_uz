<?php

namespace Tests\Feature;

use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class MyVacancyTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_create_goes_to_moderation(): void
    {
        Sanctum::actingAs($this->parentUser());

        $this->postJson('/api/v1/my/vacancies', $this->vacancyPayload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->getJson('/api/v1/vacancies')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/my/vacancies')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_validation(): void
    {
        Sanctum::actingAs($this->parentUser());

        $this->postJson('/api/v1/my/vacancies', [])->assertUnprocessable()->assertJsonValidationErrors(['title', 'region_id', 'work_format']);
        $this->postJson('/api/v1/my/vacancies', $this->vacancyPayload(['children_ages' => [2, 18]]))->assertUnprocessable()->assertJsonValidationErrors(['children_ages.1']);
        $this->postJson('/api/v1/my/vacancies', $this->vacancyPayload(['salary_min' => 5_000_000, 'salary_max' => 1_000_000]))->assertUnprocessable()->assertJsonValidationErrors(['salary_max']);
    }

    public function test_nanny_only_user_cannot_manage_vacancies(): void
    {
        Sanctum::actingAs($this->nannyUser());

        $this->postJson('/api/v1/my/vacancies', $this->vacancyPayload())->assertForbidden();
        $this->getJson('/api/v1/my/vacancies')->assertForbidden();
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->postJson('/api/v1/my/vacancies', $this->vacancyPayload())->assertUnauthorized();
    }

    public function test_limit_of_five_open_vacancies(): void
    {
        $user = $this->parentUser();
        Sanctum::actingAs($user);

        Vacancy::factory()->count(3)->create(['parent_profile_id' => $user->parentProfile->id]);
        Vacancy::factory()->count(2)->active()->create(['parent_profile_id' => $user->parentProfile->id]);
        Vacancy::factory()->create(['parent_profile_id' => $user->parentProfile->id, 'status' => VacancyStatus::Closed]);

        $this->postJson('/api/v1/my/vacancies', $this->vacancyPayload())->assertUnprocessable()->assertJsonValidationErrors('vacancy');

        // Yopilgan e'lon o'rni bo'shatadi
        Vacancy::first()->update(['status' => VacancyStatus::Closed]);
        $this->postJson('/api/v1/my/vacancies', $this->vacancyPayload())->assertCreated();
    }

    public function test_editing_active_text_returns_to_pending_but_other_fields_do_not(): void
    {
        $user = $this->parentUser();
        Sanctum::actingAs($user);
        $vacancy = Vacancy::factory()->active()->create($this->vacancyPayload() + ['parent_profile_id' => $user->parentProfile->id]);

        $this->putJson('/api/v1/my/vacancies/'.$vacancy->id, $this->vacancyPayload(['salary_max' => 4_500_000, 'title' => $vacancy->title, 'description' => $vacancy->description]))
            ->assertOk()->assertJsonPath('data.status', 'active');

        $this->putJson('/api/v1/my/vacancies/'.$vacancy->id, $this->vacancyPayload(['description' => 'Yangi tavsif: hafta oxiri ham ishlash kerak bo\'ladi.']))
            ->assertOk()->assertJsonPath('data.status', 'pending');
    }

    public function test_other_parent_cannot_edit_delete_or_close(): void
    {
        $owner = $this->parentUser();
        $vacancy = Vacancy::factory()->active()->create(['parent_profile_id' => $owner->parentProfile->id]);

        Sanctum::actingAs($this->parentUser());
        $this->putJson('/api/v1/my/vacancies/'.$vacancy->id, $this->vacancyPayload())->assertForbidden();
        $this->deleteJson('/api/v1/my/vacancies/'.$vacancy->id)->assertForbidden();
        $this->postJson('/api/v1/my/vacancies/'.$vacancy->id.'/close', ['found' => true])->assertForbidden();
    }

    public function test_close_found_or_not_and_delete(): void
    {
        $user = $this->parentUser();
        Sanctum::actingAs($user);
        $a = Vacancy::factory()->active()->create(['parent_profile_id' => $user->parentProfile->id]);
        $b = Vacancy::factory()->active()->create(['parent_profile_id' => $user->parentProfile->id]);

        $this->postJson("/api/v1/my/vacancies/{$a->id}/close", ['found' => true])->assertOk()->assertJsonPath('data.status', 'found');
        $this->postJson("/api/v1/my/vacancies/{$b->id}/close", ['found' => false])->assertOk()->assertJsonPath('data.status', 'closed');
        $this->postJson("/api/v1/my/vacancies/{$a->id}/close", ['found' => false])->assertForbidden();
        $this->postJson("/api/v1/my/vacancies/{$a->id}/close", [])->assertUnprocessable();

        $this->deleteJson('/api/v1/my/vacancies/'.$a->id)->assertNoContent();
        $this->assertSoftDeleted($a);
    }
}
