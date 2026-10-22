<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class ApplicationFlowTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function vacancyOf($parent, array $attrs = []): Vacancy
    {
        return Vacancy::factory()->active()->create($attrs + ['parent_profile_id' => $parent->parentProfile->id]);
    }

    public function test_nanny_applies_once(): void
    {
        $vacancy = $this->vacancyOf($this->parentUser());
        $nanny = $this->nannyUser(['status' => 'active']);
        Sanctum::actingAs($nanny);

        $this->postJson("/api/v1/vacancies/{$vacancy->id}/apply", ['message' => 'Salom, tajribam bor.'])
            ->assertCreated()->assertJsonPath('data.status', 'sent');
        $this->postJson("/api/v1/vacancies/{$vacancy->id}/apply")->assertStatus(409);

        $this->getJson('/api/v1/my/applications')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.vacancy.id', $vacancy->id);
    }

    public function test_cannot_apply_to_inactive_vacancy_or_with_inactive_profile(): void
    {
        $parent = $this->parentUser();
        $pending = Vacancy::factory()->create(['parent_profile_id' => $parent->parentProfile->id]);
        $active = $this->vacancyOf($parent);

        Sanctum::actingAs($this->nannyUser(['status' => 'active']));
        $this->postJson("/api/v1/vacancies/{$pending->id}/apply")->assertUnprocessable();

        Sanctum::actingAs($this->nannyUser(['status' => 'pending']));
        $this->postJson("/api/v1/vacancies/{$active->id}/apply")->assertUnprocessable()->assertJsonValidationErrors('nanny_profile');
    }

    public function test_parent_cannot_apply(): void
    {
        $parent = $this->parentUser();
        $vacancy = $this->vacancyOf($parent);

        Sanctum::actingAs($this->parentUser());
        $this->postJson("/api/v1/vacancies/{$vacancy->id}/apply")->assertForbidden();
        Sanctum::actingAs($parent);
        $this->postJson("/api/v1/vacancies/{$vacancy->id}/apply")->assertForbidden();
    }

    public function test_only_owner_sees_and_updates_applications(): void
    {
        $owner = $this->parentUser();
        $vacancy = $this->vacancyOf($owner);
        $nanny = $this->nannyUser(['status' => 'active']);
        $application = Application::create(['vacancy_id' => $vacancy->id, 'nanny_profile_id' => $nanny->nannyProfile->id, 'message' => 'Salom']);

        Sanctum::actingAs($this->parentUser());
        $this->getJson("/api/v1/my/vacancies/{$vacancy->id}/applications")->assertForbidden();
        $this->patchJson("/api/v1/applications/{$application->id}", ['status' => 'shortlisted'])->assertForbidden();

        Sanctum::actingAs($owner);
        $response = $this->getJson("/api/v1/my/vacancies/{$vacancy->id}/applications")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nanny.name', 'Dilnoza K.');
        $this->assertStringNotContainsString('998907778899', $response->getContent());

        $this->patchJson("/api/v1/applications/{$application->id}", ['status' => 'shortlisted'])
            ->assertOk()->assertJsonPath('data.status', 'shortlisted');
        $this->patchJson("/api/v1/applications/{$application->id}", ['status' => 'sent'])->assertUnprocessable();
        $this->patchJson("/api/v1/applications/{$application->id}", ['status' => 'nonsense'])->assertUnprocessable();
    }
}
