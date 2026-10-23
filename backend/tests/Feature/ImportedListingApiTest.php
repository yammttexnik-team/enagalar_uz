<?php

namespace Tests\Feature;

use App\Enums\ImportedListingStatus;
use App\Enums\VacancyStatus;
use App\Models\ImportedListing;
use App\Models\NannyProfile;
use App\Models\Region;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImportedListingApiTest extends TestCase
{
    use RefreshDatabase;

    private int $post = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function listing(array $attrs = []): ImportedListing
    {
        $id = $this->post++;

        return ImportedListing::create($attrs + [
            'type' => ImportedListing::TYPE_VACANCY,
            'status' => ImportedListingStatus::Active,
            'source_channel' => 'enagalar_uz',
            'source_post_id' => $id,
            'source_url' => 'https://t.me/enagalar_uz/'.$id,
            'source_posted_at' => now()->subDay(),
            'raw_text' => 'Enaga kerak, tel +998 90 123 45 67',
            'content_hash' => hash('sha256', (string) $id),
            'title' => 'Ikki yoshli bolaga enaga kerak',
            'description' => 'Chilonzorda, kunduzi',
            'region_id' => Region::value('id'),
            'work_format' => 'full_time',
            'price_min' => 3_000_000,
            'price_max' => 4_000_000,
            'price_unit' => 'month',
            'children_ages' => [2],
            'contact_hashes' => [ImportedListing::contactHash('+998 90 123 45 67')],
            'published_at' => now(),
            'expires_at' => now()->addDays(14),
        ]);
    }

    public function test_public_list_shows_only_visible_and_hides_contacts(): void
    {
        $visible = $this->listing();
        $this->listing(['status' => ImportedListingStatus::Pending]);
        $this->listing(['status' => ImportedListingStatus::Archived]);
        $this->listing(['status' => ImportedListingStatus::Removed]);
        $this->listing(['expires_at' => now()->subDay()]);
        $this->listing(['type' => ImportedListing::TYPE_NANNY]);

        $response = $this->getJson('/api/v1/imported?type=vacancy')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonPath('data.0.source.channel', 'enagalar_uz')
            ->assertJsonPath('data.0.price.min', 3_000_000);

        $body = $response->getContent();
        $this->assertStringNotContainsString('raw_text', $body);
        $this->assertStringNotContainsString('contact_hashes', $body);
        $this->assertStringNotContainsString('123 45 67', $body);

        $this->getJson('/api/v1/imported/'.$visible->id)->assertOk()->assertJsonMissingPath('data.raw_text');
        $pending = ImportedListing::where('status', 'pending')->first();
        $this->getJson('/api/v1/imported/'.$pending->id)->assertNotFound();
    }

    public function test_view_counted_once_per_viewer(): void
    {
        $listing = $this->listing();

        $this->getJson('/api/v1/imported/'.$listing->id)->assertOk();
        $this->getJson('/api/v1/imported/'.$listing->id)->assertOk();

        $this->assertSame(1, $listing->fresh()->views_count);
    }

    public function test_removal_request_hides_listing(): void
    {
        $listing = $this->listing();

        $this->postJson('/api/v1/imported/'.$listing->id.'/removal-request', ['reason' => 'Enaga topildi'])
            ->assertStatus(202)->assertJsonStructure(['message']);

        $listing->refresh();
        $this->assertSame(ImportedListingStatus::Removed, $listing->status);
        $this->assertSame('removal_request', $listing->archive_reason);
        $this->assertSame('Enaga topildi', $listing->removal_reason);
        $this->assertNotNull($listing->removal_requested_at);
        $this->getJson('/api/v1/imported')->assertJsonCount(0, 'data');
    }

    public function test_claims_match_by_phone_and_by_username(): void
    {
        $byPhone = $this->listing();
        $byUsername = $this->listing(['contact_hashes' => [ImportedListing::contactHash('@Aziza_Ona')]]);
        $this->listing(['contact_hashes' => [ImportedListing::contactHash('+998 91 000 00 00')]]);
        $this->listing(['status' => ImportedListingStatus::Archived]);

        Sanctum::actingAs(User::factory()->create(['phone' => '+998901234567', 'telegram_username' => null]));
        $this->getJson('/api/v1/my/imported-claims')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $byPhone->id);
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.imported_claims_count', 1);

        Sanctum::actingAs(User::factory()->create(['phone' => '+998935556677', 'telegram_username' => 'aziza_ona']));
        $this->getJson('/api/v1/my/imported-claims')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $byUsername->id);
    }

    public function test_accept_vacancy_creates_pending_vacancy_with_top_gift(): void
    {
        $listing = $this->listing();
        $user = User::factory()->create(['phone' => '+998901234567']);
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/v1/my/imported-claims/'.$listing->id.'/accept')->assertOk()
            ->assertJsonPath('data.type', 'vacancy')->json('data.id');

        $vacancy = Vacancy::findOrFail($id);
        $this->assertSame(VacancyStatus::Pending, $vacancy->status);
        $this->assertSame($user->id, $vacancy->parentProfile->user_id);
        $this->assertSame(3_000_000, (int) $vacancy->salary_min);
        $this->assertTrue($vacancy->top_until->isAfter(now()->addDays(config('imports.claim_top_days') - 1)));
        $this->assertTrue($user->fresh()->hasRole('parent'));

        $listing->refresh();
        $this->assertSame(ImportedListingStatus::Claimed, $listing->status);
        $this->assertSame($user->id, $listing->claimed_by_user_id);
        $this->assertSame('claimed', $listing->archive_reason);

        $this->getJson('/api/v1/me')->assertJsonPath('data.imported_claims_count', 0);
        $this->postJson('/api/v1/my/imported-claims/'.$listing->id.'/accept')->assertForbidden();
    }

    public function test_accept_nanny_fills_only_empty_profile_fields(): void
    {
        $listing = $this->listing([
            'type' => ImportedListing::TYPE_NANNY,
            'description' => 'Tajribali enagaman',
            'experience_years' => 5,
            'price_min' => 50_000,
            'price_unit' => 'hour',
            'languages' => ['uz', 'ru'],
        ]);
        $user = User::factory()->create(['phone' => '+998901234567']);
        $user->assignRole('nanny');
        NannyProfile::factory()->create(['user_id' => $user->id, 'bio' => 'O\'zim yozgan matn', 'experience_years' => 0, 'price_amount' => null, 'languages' => null]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/my/imported-claims/'.$listing->id.'/accept')->assertOk()->assertJsonPath('data.type', 'nanny');

        $profile = $user->nannyProfile()->first();
        $this->assertSame('O\'zim yozgan matn', $profile->bio);
        $this->assertSame(5, $profile->experience_years);
        $this->assertSame(50_000, (int) $profile->price_amount);
        $this->assertSame(['uz', 'ru'], $profile->languages);
        $this->assertTrue($profile->isTop());
        $this->assertSame(ImportedListingStatus::Claimed, $listing->fresh()->status);
    }

    public function test_dismiss_not_mine_and_remove(): void
    {
        $a = $this->listing();
        $b = $this->listing();
        Sanctum::actingAs(User::factory()->create(['phone' => '+998901234567']));

        $this->postJson('/api/v1/my/imported-claims/'.$a->id.'/dismiss', ['action' => 'bad'])->assertUnprocessable();
        $this->postJson('/api/v1/my/imported-claims/'.$a->id.'/dismiss', ['action' => 'not_mine'])->assertNoContent();
        $this->postJson('/api/v1/my/imported-claims/'.$b->id.'/dismiss', ['action' => 'remove'])->assertNoContent();

        $this->assertNotNull($a->fresh()->claim_dismissed_at);
        $this->assertSame(ImportedListingStatus::Active, $a->fresh()->status);
        $this->assertSame(ImportedListingStatus::Removed, $b->fresh()->status);
        $this->getJson('/api/v1/my/imported-claims')->assertJsonCount(0, 'data');
    }

    public function test_non_matching_user_gets_403(): void
    {
        $listing = $this->listing();
        Sanctum::actingAs(User::factory()->create(['phone' => '+998977777777', 'telegram_username' => null]));

        $this->getJson('/api/v1/my/imported-claims')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/me')->assertJsonPath('data.imported_claims_count', 0);
        $this->postJson('/api/v1/my/imported-claims/'.$listing->id.'/accept')->assertForbidden();
        $this->postJson('/api/v1/my/imported-claims/'.$listing->id.'/dismiss', ['action' => 'remove'])->assertForbidden();
        $this->assertSame(ImportedListingStatus::Active, $listing->fresh()->status);
        $this->assertSame(0, Vacancy::count());
    }

    public function test_claims_require_auth(): void
    {
        $this->getJson('/api/v1/my/imported-claims')->assertUnauthorized();
    }
}
