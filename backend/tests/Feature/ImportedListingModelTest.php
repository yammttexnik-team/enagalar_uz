<?php

namespace Tests\Feature;

use App\Enums\ImportedListingStatus;
use App\Models\ImportedListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportedListingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_hash_normalizes_phone_and_username(): void
    {
        $this->assertSame(ImportedListing::contactHash('+998 90 123-45-67'), ImportedListing::contactHash('901234567'));
        $this->assertSame(ImportedListing::contactHash('@Dilnoza_Uz'), ImportedListing::contactHash('dilnoza_uz'));
        $this->assertNotSame(ImportedListing::contactHash('901234567'), ImportedListing::contactHash('901234568'));
        $this->assertNull(ImportedListing::contactHash('12345'));
    }

    public function test_user_hashes_match_listing_and_raw_text_is_hidden(): void
    {
        $user = User::factory()->create(['phone' => '+998901234567', 'telegram_username' => 'Dilnoza_Uz']);
        $listing = ImportedListing::create([
            'type' => ImportedListing::TYPE_NANNY, 'status' => ImportedListingStatus::Active,
            'source_channel' => 'enagalar', 'source_post_id' => 10, 'source_url' => 'https://t.me/enagalar/10',
            'raw_text' => 'Enagaman, tel 90 123 45 67', 'content_hash' => str_repeat('a', 64),
            'contact_hashes' => [ImportedListing::contactHash('90 123 45 67')],
        ]);

        $this->assertNotEmpty(array_intersect(ImportedListing::hashesForUser($user), $listing->contact_hashes));
        $this->assertArrayNotHasKey('raw_text', $listing->toArray());
        $this->assertArrayNotHasKey('contact_hashes', $listing->toArray());
        $this->assertSame(1, ImportedListing::visible()->count());

        $listing->archive('deleted');
        $this->assertSame(0, ImportedListing::visible()->count());
    }
}
