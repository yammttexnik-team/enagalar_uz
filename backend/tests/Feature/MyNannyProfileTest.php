<?php

namespace Tests\Feature;

use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class MyNannyProfileTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function fullData(): array
    {
        return [
            'region_id' => Region::value('id'),
            'bio' => str_repeat('Bolalarni yaxshi ko\'raman. ', 5),
            'experience_years' => 4,
            'price_amount' => 3_000_000,
            'price_unit' => 'month',
            'work_formats' => ['full_time', 'hourly'],
            'languages' => ['uz', 'ru'],
            'age_groups' => ['toddler'],
            'skills' => ['cooking', 'first-aid'],
        ];
    }

    public function test_update_and_show_with_skills_sync(): void
    {
        $user = $this->nannyUser(['bio' => null, 'region_id' => null]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/my/nanny-profile', $this->fullData())
            ->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(2, 'data.skills');

        $this->putJson('/api/v1/my/nanny-profile', ['skills' => ['cooking']])->assertOk()->assertJsonCount(1, 'data.skills');
        $this->getJson('/api/v1/my/nanny-profile')->assertOk()->assertJsonPath('data.experience_years', 4);
    }

    public function test_update_validates_enums_and_district_region(): void
    {
        Sanctum::actingAs($this->nannyUser());
        $districtOfOtherRegion = Region::where('slug', 'tashkent-city')->first()->districts()->value('id');
        $region = Region::where('slug', '!=', 'tashkent-city')->first();

        $this->putJson('/api/v1/my/nanny-profile', ['work_formats' => ['bogus']])->assertUnprocessable();
        $this->putJson('/api/v1/my/nanny-profile', ['region_id' => $region->id, 'district_id' => $districtOfOtherRegion])
            ->assertUnprocessable()->assertJsonValidationErrors('district_id');
    }

    public function test_submit_requires_minimum_fields(): void
    {
        Sanctum::actingAs($this->nannyUser(['bio' => 'qisqa', 'price_amount' => null, 'region_id' => null, 'work_formats' => [], 'languages' => null]));

        $this->postJson('/api/v1/my/nanny-profile/submit')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['region_id', 'bio', 'price_amount', 'work_formats', 'languages']);
    }

    public function test_submit_moves_to_pending_and_cannot_repeat(): void
    {
        Sanctum::actingAs($this->nannyUser(['bio' => null, 'region_id' => null]));
        $this->putJson('/api/v1/my/nanny-profile', $this->fullData())->assertOk();

        $this->postJson('/api/v1/my/nanny-profile/submit')->assertOk()->assertJsonPath('data.status', 'pending');
        $this->postJson('/api/v1/my/nanny-profile/submit')->assertUnprocessable();
        // Moderatsiyadagi anketa ochiq qidiruvda ko'rinmaydi
        $this->getJson('/api/v1/nannies')->assertJsonCount(0, 'data');
    }

    public function test_availability_toggle(): void
    {
        Sanctum::actingAs($this->nannyUser());

        $this->patchJson('/api/v1/my/nanny-profile/availability', ['is_available' => false])->assertOk()->assertJsonPath('data.is_available', false);
        $this->patchJson('/api/v1/my/nanny-profile/availability', [])->assertUnprocessable();
    }

    public function test_parent_cannot_access_nanny_profile(): void
    {
        Sanctum::actingAs($this->parentUser());

        $this->getJson('/api/v1/my/nanny-profile')->assertForbidden();
    }
}
