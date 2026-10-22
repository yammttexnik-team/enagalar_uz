<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Filament\Resources\Documents\Pages\ViewDocument;
use App\Filament\Resources\IdentityVerifications\Pages\ViewIdentityVerification;
use App\Http\Controllers\Admin\ReviewFileController;
use App\Models\Badge;
use App\Models\Document;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake(IdentityVerification::DISK);
        $this->admin = User::factory()->create()->assignRole(Role::Admin->value);
    }

    private function verification(): IdentityVerification
    {
        return IdentityVerification::create([
            'user_id' => User::factory()->create()->id,
            'full_name' => 'Karimova Dilnoza',
            'passport_series' => 'AD',
            'passport_number' => '1234567',
            'pinfl' => '41234567890123',
            'birth_date' => '1990-05-12',
            'document_path' => UploadedFile::fake()->image('p.jpg')->store('passports', IdentityVerification::DISK),
        ]);
    }

    public function test_admin_pages_render(): void
    {
        $verification = $this->verification();
        $nanny = User::factory()->create()->nannyProfile()->create(['bio' => 'Tajribali enaga', 'languages' => ['uz', 'ru']]);
        $vacancy = User::factory()->create()->parentProfile()->create()->vacancies()->create([
            'region_id' => 1, 'title' => 'Enaga kerak', 'description' => '3 yoshli qizimga', 'work_format' => 'full_time',
        ]);

        $this->actingAs($this->admin);

        foreach (["/admin/nanny-profiles/{$nanny->id}", "/admin/vacancies/{$vacancy->id}", "/admin/users/{$this->admin->id}/edit", '/admin', '/admin/users', '/admin/badges', '/admin/badges/create', '/admin/identity-verifications',
            "/admin/identity-verifications/{$verification->id}", '/admin/documents', '/admin/nanny-profiles', '/admin/vacancies'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_regular_user_cannot_open_admin(): void
    {
        $this->actingAs(User::factory()->create()->assignRole(Role::Nanny->value))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_moderator_approves_passport_from_panel(): void
    {
        $verification = $this->verification();

        $this->actingAs($this->admin);
        Livewire::test(ViewIdentityVerification::class, ['record' => $verification->getRouteKey()])
            ->callAction('approve')
            ->assertHasNoActionErrors();

        $this->assertSame(VerificationStatus::Approved, $verification->fresh()->status);
        $this->assertTrue($verification->user->isVerified());
    }

    public function test_moderator_rejects_with_reason(): void
    {
        $verification = $this->verification();

        $this->actingAs($this->admin);
        Livewire::test(ViewIdentityVerification::class, ['record' => $verification->getRouteKey()])
            ->callAction('reject', data: ['reason' => 'Rasm xira'])
            ->assertHasNoActionErrors();

        $this->assertSame('Rasm xira', $verification->fresh()->rejection_reason);
        $this->assertNull($verification->fresh()->document_path);
    }

    public function test_dispensary_document_approval_grants_badge_with_expiry(): void
    {
        $user = User::factory()->create();
        $document = Document::create([
            'user_id' => $user->id,
            'badge_id' => Badge::where('slug', Badge::DISPENSARY_CLEAR)->value('id'),
            'type' => 'dispensary',
            'path' => UploadedFile::fake()->image('s.jpg')->store('documents', Document::DISK),
        ]);

        $this->actingAs($this->admin);
        Livewire::test(ViewDocument::class, ['record' => $document->getRouteKey()])->callAction('approve');

        $badge = $user->activeBadges()->where('slug', Badge::DISPENSARY_CLEAR)->first();
        $this->assertNotNull($badge);
        $this->assertTrue($badge->pivot->expires_at->between(now()->addDays(364), now()->addDays(366)));
        $this->assertNull($document->fresh()->path);
    }

    public function test_review_file_requires_signature_and_panel_access(): void
    {
        $verification = $this->verification();
        $url = ReviewFileController::url('identity', $verification->id);

        $this->actingAs($this->admin)->get($url)->assertOk();
        $this->actingAs($this->admin)->get("/admin/review-file/identity/{$verification->id}")->assertForbidden();
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }
}
