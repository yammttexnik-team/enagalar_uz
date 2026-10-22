<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\IdentityVerification;
use App\Models\Region;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class ModerationFixesTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_editing_bio_of_active_profile_returns_to_moderation(): void
    {
        Sanctum::actingAs($this->nannyUser(['status' => 'active']));

        // Moderatsiyaga kirmaydigan maydon — faol qoladi
        $this->putJson('/api/v1/my/nanny-profile', ['price_amount' => 5_000_000])
            ->assertOk()->assertJsonPath('data.status', 'active');

        $this->putJson('/api/v1/my/nanny-profile', ['bio' => str_repeat('Yangi matn. ', 10)])
            ->assertOk()->assertJsonPath('data.status', 'pending');
    }

    public function test_hidden_profile_cannot_be_submitted(): void
    {
        Sanctum::actingAs($this->nannyUser(['status' => 'hidden']));

        $this->postJson('/api/v1/my/nanny-profile/submit')->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_region_change_without_district_clears_district(): void
    {
        $user = $this->nannyUser();
        $profile = $user->nannyProfile;
        $old = Region::where('slug', 'tashkent-city')->first();
        $profile->update(['region_id' => $old->id, 'district_id' => $old->districts()->value('id')]);
        $new = Region::where('id', '!=', $old->id)->first();

        Sanctum::actingAs($user);
        $this->putJson('/api/v1/my/nanny-profile', ['region_id' => $new->id])->assertOk();

        $this->assertNull($profile->fresh()->district_id);
    }

    public function test_application_status_update_with_deleted_vacancy_is_denied_not_500(): void
    {
        $owner = $this->parentUser();
        $vacancy = Vacancy::factory()->active()->create(['parent_profile_id' => $owner->parentProfile->id]);
        $nanny = $this->nannyUser(['status' => 'active']);
        $application = Application::create(['vacancy_id' => $vacancy->id, 'nanny_profile_id' => $nanny->nannyProfile->id, 'message' => 'Salom']);
        $vacancy->delete();

        Sanctum::actingAs($owner);
        $this->patchJson("/api/v1/applications/{$application->id}", ['status' => 'viewed'])->assertForbidden();
    }

    public function test_application_status_transitions(): void
    {
        $owner = $this->parentUser();
        $vacancy = Vacancy::factory()->active()->create(['parent_profile_id' => $owner->parentProfile->id]);
        $nanny = $this->nannyUser(['status' => 'active']);
        $application = Application::create(['vacancy_id' => $vacancy->id, 'nanny_profile_id' => $nanny->nannyProfile->id, 'message' => 'Salom']);
        Sanctum::actingAs($owner);
        $url = "/api/v1/applications/{$application->id}";

        $this->patchJson($url, ['status' => 'shortlisted'])->assertOk();
        $this->patchJson($url, ['status' => 'viewed'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->patchJson($url, ['status' => 'rejected'])->assertOk();
        // Rad etilgan — yakuniy holat
        $this->patchJson($url, ['status' => 'shortlisted'])->assertUnprocessable();
        $this->patchJson($url, ['status' => 'rejected'])->assertUnprocessable();
    }

    public function test_salary_max_without_salary_min_is_accepted(): void
    {
        $parent = $this->parentUser();
        Sanctum::actingAs($parent);

        $payload = $this->vacancyPayload(['salary_max' => 4_000_000]);
        unset($payload['salary_min']);
        $this->postJson('/api/v1/my/vacancies', $payload)->assertCreated();

        $this->postJson('/api/v1/my/vacancies', $this->vacancyPayload(['salary_min' => 5_000_000, 'salary_max' => 4_000_000]))
            ->assertUnprocessable()->assertJsonValidationErrors('salary_max');
    }

    public function test_verification_prune_clears_pii_after_retention(): void
    {
        $old = IdentityVerification::create([
            'user_id' => User::factory()->create()->id, 'full_name' => 'A', 'passport_series' => 'AD',
            'passport_number' => '1111111', 'pinfl' => '41234567890123', 'birth_date' => '1990-01-01', 'status' => 'approved',
        ]);
        $old->forceFill(['reviewed_at' => now()->subDays(31)])->save();
        $recent = IdentityVerification::create([
            'user_id' => User::factory()->create()->id, 'full_name' => 'B', 'passport_series' => 'AD',
            'passport_number' => '2222222', 'pinfl' => '41234567890123', 'birth_date' => '1990-01-01', 'status' => 'approved',
        ]);
        $recent->forceFill(['reviewed_at' => now()->subDays(5)])->save();
        $pending = IdentityVerification::create([
            'user_id' => User::factory()->create()->id, 'full_name' => 'C', 'passport_series' => 'AD',
            'passport_number' => '3333333', 'pinfl' => '41234567890123', 'birth_date' => '1990-01-01',
        ]);

        $this->artisan('verification:prune')->assertSuccessful();

        $this->assertNull($old->fresh()->passport_number);
        $this->assertNull($old->fresh()->pinfl);
        $this->assertSame('2222222', $recent->fresh()->passport_number);
        $this->assertSame('3333333', $pending->fresh()->passport_number);
    }
}
