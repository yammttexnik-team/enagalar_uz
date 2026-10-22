<?php

namespace Tests\Feature;

use App\Enums\BonusType;
use App\Models\Badge;
use App\Models\BonusTransaction;
use App\Models\Document;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationBonusTest extends TestCase
{
    use RefreshDatabase;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake(IdentityVerification::DISK);
        $this->moderator = User::factory()->create();
    }

    private function verification(User $user): IdentityVerification
    {
        return IdentityVerification::create([
            'user_id' => $user->id,
            'full_name' => 'Karimova Dilnoza',
            'passport_series' => 'AD',
            'passport_number' => '1234567',
            'pinfl' => '41234567890123',
            'birth_date' => '1990-05-12',
        ]);
    }

    private function document(User $user): Document
    {
        return Document::create([
            'user_id' => $user->id,
            'badge_id' => Badge::where('slug', Badge::MEDICAL)->value('id'),
            'type' => 'medical',
        ]);
    }

    public function test_passport_approval_awards_bonus_once(): void
    {
        $user = User::factory()->create();
        $verification = $this->verification($user);

        $verification->approve($this->moderator);
        $verification->approve($this->moderator);

        $this->assertSame(15000, $user->bonusBalance());
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $user->id, 'type' => BonusType::Verification->value, 'reference' => 'verification:'.$verification->id,
        ]);
    }

    public function test_rejection_awards_nothing(): void
    {
        $user = User::factory()->create();
        $this->verification($user)->reject($this->moderator, 'Rasm aniq emas');
        $this->document($user)->reject($this->moderator, 'Eski');

        $this->assertSame(0, $user->bonusBalance());
    }

    public function test_document_bonuses_are_capped_with_passport_bonus(): void
    {
        $user = User::factory()->create();
        $this->verification($user)->approve($this->moderator);

        $docs = collect(range(1, 4))->map(fn () => $this->document($user));
        $docs->each(fn (Document $d) => $d->approve($this->moderator));
        $docs->first()->approve($this->moderator);

        // 15 000 + 3 × 5 000 = 30 000 (maksimum); 4-hujjat uchun bonus yo'q.
        $this->assertSame(30000, $user->bonusBalance());
        $this->assertSame(3, BonusTransaction::where('type', BonusType::Document)->count());
    }

    public function test_verification_bonus_respects_lifetime_cap(): void
    {
        config(['bonus.lifetime_cap' => 20000]);
        $user = User::factory()->create();
        $this->verification($user)->approve($this->moderator);
        $this->document($user)->approve($this->moderator);
        $this->document($user)->approve($this->moderator);

        $this->assertSame(20000, $user->bonusBalance());
    }
}
