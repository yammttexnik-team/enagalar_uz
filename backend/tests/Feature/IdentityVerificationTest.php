<?php

namespace Tests\Feature;

use App\Enums\VerificationStatus;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // In-memory baza bir marta migratsiya qilinadi, shuning uchun seed har testda chaqiriladi.
        $this->seed();
    }

    private function makeVerification(User $user): IdentityVerification
    {
        $path = UploadedFile::fake()->image('passport.jpg')->store('passports', IdentityVerification::DISK);

        return IdentityVerification::create([
            'user_id' => $user->id,
            'full_name' => 'Karimova Dilnoza',
            'passport_series' => 'AD',
            'passport_number' => '1234567',
            'pinfl' => '41234567890123',
            'birth_date' => '1990-05-12',
            'document_path' => $path,
        ]);
    }

    public function test_passport_data_is_encrypted_at_rest(): void
    {
        Storage::fake(IdentityVerification::DISK);
        $verification = $this->makeVerification(User::factory()->create());

        $raw = DB::table('identity_verifications')->find($verification->id);

        $this->assertNotSame('41234567890123', $raw->pinfl);
        $this->assertSame('41234567890123', $verification->fresh()->pinfl);
        $this->assertArrayNotHasKey('pinfl', $verification->toArray());
    }

    public function test_approval_grants_verified_badge_and_deletes_document(): void
    {
        Storage::fake(IdentityVerification::DISK);
        $user = User::factory()->create();
        $moderator = User::factory()->create();
        $verification = $this->makeVerification($user);
        $path = $verification->document_path;

        $verification->approve($moderator);

        $this->assertSame(VerificationStatus::Approved, $verification->fresh()->status);
        $this->assertNull($verification->fresh()->document_path);
        Storage::disk(IdentityVerification::DISK)->assertMissing($path);
        $this->assertTrue($user->isVerified());
    }

    public function test_rejection_does_not_grant_badge(): void
    {
        Storage::fake(IdentityVerification::DISK);
        $user = User::factory()->create();
        $verification = $this->makeVerification($user);

        $verification->reject(User::factory()->create(), 'Rasm aniq emas');

        $this->assertSame(VerificationStatus::Rejected, $verification->fresh()->status);
        $this->assertFalse($user->isVerified());
    }
}
