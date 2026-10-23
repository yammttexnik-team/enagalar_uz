<?php

namespace Tests\Feature;

use App\Enums\VerificationStatus;
use App\Models\Badge;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('private');
    }

    private function login(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    private function identityPayload(array $over = []): array
    {
        return $over + [
            'full_name' => 'Karimova Dilnoza',
            'passport_series' => 'AD',
            'passport_number' => '1234567',
            'pinfl' => '41234567890123',
            'birth_date' => now()->subYears(30)->toDateString(),
            'photo' => UploadedFile::fake()->image('p.jpg'),
            'consent' => '1',
        ];
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v1/my/verification')->assertUnauthorized();
    }

    public function test_status_summary_for_new_user(): void
    {
        $this->login();

        $res = $this->getJson('/api/v1/my/verification')->assertOk();
        $res->assertJsonPath('data.identity.status', 'none');
        $res->assertJsonPath('data.badges', []);
        $res->assertJsonMissingPath('data.documents');
        $res->assertJsonMissingPath('data.available_badges');
    }

    public function test_identity_submission_success_and_privacy(): void
    {
        $user = $this->login();

        $res = $this->postJson('/api/v1/my/verification/identity', $this->identityPayload())
            ->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->assertStringNotContainsString('41234567890123', $res->getContent());

        $iv = IdentityVerification::firstOrFail();
        $this->assertSame($user->id, $iv->user_id);
        $this->assertStringStartsWith("identity/{$user->id}/", $iv->document_path);
        Storage::disk('private')->assertExists($iv->document_path);
        $this->assertDatabaseHas('consents', ['user_id' => $user->id, 'type' => 'passport', 'version' => config('enagalar.consent_version')]);

        $status = $this->getJson('/api/v1/my/verification')->assertOk();
        $status->assertJsonPath('data.identity.status', 'pending');
        $body = $status->getContent();
        foreach (['41234567890123', '1234567', 'passport_', 'pinfl', 'document_path', $iv->document_path, 'identity\/'] as $needle) {
            $this->assertStringNotContainsString($needle, $body);
        }
    }

    public function test_identity_validation(): void
    {
        $this->login();

        $this->postJson('/api/v1/my/verification/identity', $this->identityPayload([
            'passport_series' => '12', 'passport_number' => '123', 'pinfl' => '1',
            'birth_date' => now()->subYears(10)->toDateString(), 'consent' => '0',
            'photo' => UploadedFile::fake()->create('x.gif', 10, 'image/gif'),
        ]))->assertUnprocessable()->assertJsonValidationErrors(
            ['passport_series', 'passport_number', 'pinfl', 'birth_date', 'photo', 'consent']
        );
        $this->postJson('/api/v1/my/verification/identity', $this->identityPayload(['birth_date' => now()->subYears(90)->toDateString()]))
            ->assertJsonValidationErrors('birth_date');
        $this->postJson('/api/v1/my/verification/identity', $this->identityPayload(['photo' => UploadedFile::fake()->image('big.jpg')->size(9000)]))
            ->assertJsonValidationErrors('photo');
        $this->assertDatabaseCount('identity_verifications', 0);
    }

    public function test_lowercase_series_is_normalized(): void
    {
        $this->login();
        $this->postJson('/api/v1/my/verification/identity', $this->identityPayload(['passport_series' => 'ad']))->assertCreated();
        $this->assertSame('AD', IdentityVerification::first()->passport_series);
    }

    public function test_identity_pending_conflict(): void
    {
        $this->login();
        $this->postJson('/api/v1/my/verification/identity', $this->identityPayload())->assertCreated();
        $this->postJson('/api/v1/my/verification/identity', $this->identityPayload())->assertStatus(409);
    }

    public function test_identity_already_verified_conflict(): void
    {
        $user = $this->login();
        Badge::where('slug', Badge::VERIFIED)->first()->grantTo($user);
        $this->postJson('/api/v1/my/verification/identity', $this->identityPayload())->assertStatus(409);
    }

    public function test_identity_daily_limit(): void
    {
        $user = $this->login();
        foreach (range(1, 3) as $i) {
            $iv = IdentityVerification::create([
                'user_id' => $user->id, 'full_name' => 'A B C D E', 'passport_series' => 'AD', 'passport_number' => '1234567',
                'pinfl' => '41234567890123', 'birth_date' => '1990-01-01', 'status' => VerificationStatus::Rejected,
            ]);
        }
        $this->postJson('/api/v1/my/verification/identity', $this->identityPayload())->assertStatus(429);

        IdentityVerification::query()->update(['created_at' => now()->subDays(2)]);
        $this->postJson('/api/v1/my/verification/identity', $this->identityPayload())->assertCreated();
    }
}
