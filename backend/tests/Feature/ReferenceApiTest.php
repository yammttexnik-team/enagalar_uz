<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // In-memory baza bir marta migratsiya qilinadi, shuning uchun seed har testda chaqiriladi.
        $this->seed();
    }

    public function test_regions_are_listed_with_districts(): void
    {
        $this->getJson('/api/v1/regions')
            ->assertOk()
            ->assertJsonCount(14, 'data')
            ->assertJsonPath('data.0.slug', 'tashkent-city')
            ->assertJsonCount(12, 'data.0.districts');
    }

    public function test_badges_are_listed(): void
    {
        $this->getJson('/api/v1/badges')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'verified']);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }
}
