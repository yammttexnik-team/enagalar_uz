<?php

namespace Tests\Feature;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Admin\ReviewFileController;
use App\Models\Badge;
use App\Models\Document;
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

    private function docPayload(array $over = []): array
    {
        return $over + [
            'badge' => Badge::MEDICAL,
            'file' => UploadedFile::fake()->create('m.pdf', 100, 'application/pdf'),
            'issued_at' => now()->subDays(10)->toDateString(),
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
        $slugs = collect($res->json('data.available_badges'))->pluck('slug')->all();
        $this->assertEqualsCanonicalizing([Badge::MEDICAL, Badge::DISPENSARY_CLEAR, Badge::CERTIFICATE], $slugs);
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

    public function test_document_upload_records_health_consent(): void
    {
        $user = $this->login();

        $res = $this->postJson('/api/v1/my/verification/documents', $this->docPayload())
            ->assertCreated()
            ->assertJsonPath('data.badge.slug', Badge::MEDICAL)
            ->assertJsonPath('data.status', 'pending');
        $this->assertStringNotContainsString('documents/', $res->getContent());

        $doc = Document::firstOrFail();
        $this->assertStringStartsWith("documents/{$user->id}/", $doc->path);
        Storage::disk('private')->assertExists($doc->path);
        $this->assertDatabaseHas('consents', ['user_id' => $user->id, 'type' => 'health']);

        $list = $this->getJson('/api/v1/my/verification')->assertOk();
        $list->assertJsonPath('data.documents.0.id', $doc->id);
        $this->assertStringNotContainsString($doc->path, $list->getContent());
        $this->assertTrue(collect($list->json('data.available_badges'))->firstWhere('slug', Badge::MEDICAL)['has_pending']);
    }

    public function test_certificate_does_not_record_health_consent(): void
    {
        $this->login();
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload(['badge' => Badge::CERTIFICATE, 'issued_at' => now()->subYears(3)->toDateString()]))
            ->assertCreated();
        $this->assertDatabaseMissing('consents', ['type' => 'health']);
    }

    public function test_document_validation(): void
    {
        $this->login();

        $this->postJson('/api/v1/my/verification/documents', $this->docPayload(['badge' => Badge::VERIFIED]))->assertJsonValidationErrors('badge');
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload(['badge' => Badge::PHONE_VERIFIED]))->assertJsonValidationErrors('badge');
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload(['badge' => 'nope']))->assertJsonValidationErrors('badge');
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload(['issued_at' => now()->addDay()->toDateString()]))->assertJsonValidationErrors('issued_at');
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload(['issued_at' => now()->subDays(400)->toDateString()]))->assertJsonValidationErrors('issued_at');
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload(['file' => UploadedFile::fake()->create('x.exe', 10)]))->assertJsonValidationErrors('file');
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload(['file' => UploadedFile::fake()->create('x.pdf', 11000, 'application/pdf')]))->assertJsonValidationErrors('file');
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload(['consent' => '']))->assertJsonValidationErrors('consent');
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_one_pending_document_per_badge(): void
    {
        $this->login();
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload())->assertCreated();
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload())->assertStatus(409);
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload(['badge' => Badge::DISPENSARY_CLEAR]))->assertCreated();
    }

    public function test_delete_own_pending_document_removes_file(): void
    {
        $this->login();
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload())->assertCreated();
        $doc = Document::firstOrFail();

        $this->deleteJson("/api/v1/my/verification/documents/{$doc->id}")->assertNoContent();
        $this->assertDatabaseCount('documents', 0);
        Storage::disk('private')->assertMissing($doc->path);
    }

    public function test_cannot_delete_foreign_or_reviewed_document(): void
    {
        $owner = User::factory()->create();
        $badge = Badge::where('slug', Badge::MEDICAL)->first();
        $path = UploadedFile::fake()->image('a.jpg')->store('documents/'.$owner->id, 'private');
        $doc = Document::create(['user_id' => $owner->id, 'badge_id' => $badge->id, 'type' => $badge->slug, 'path' => $path, 'issued_at' => now(), 'status' => VerificationStatus::Pending]);

        $this->login();
        $this->deleteJson("/api/v1/my/verification/documents/{$doc->id}")->assertNotFound();
        Storage::disk('private')->assertExists($path);

        Sanctum::actingAs($owner);
        $doc->update(['status' => VerificationStatus::Approved]);
        $this->deleteJson("/api/v1/my/verification/documents/{$doc->id}")->assertStatus(409);
        $this->assertDatabaseHas('documents', ['id' => $doc->id]);
    }

    public function test_moderator_can_stream_pdf(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->login();
        $this->postJson('/api/v1/my/verification/documents', $this->docPayload())->assertCreated();
        $doc = Document::firstOrFail();

        $res = $this->actingAs($admin)->get(ReviewFileController::url('document', $doc->id));
        $res->assertOk();
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));
    }
}
