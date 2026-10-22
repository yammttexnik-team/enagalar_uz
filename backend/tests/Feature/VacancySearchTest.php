<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class VacancySearchTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function ids(string $query = ''): array
    {
        return collect($this->getJson('/api/v1/vacancies'.$query)->assertOk()->json('data'))->pluck('id')->all();
    }

    private function make(array $attrs = []): Vacancy
    {
        return Vacancy::factory()->active()->create($attrs + ['parent_profile_id' => $this->parentUser()->parentProfile->id]);
    }

    private function vacancyOf($parent): Vacancy
    {
        return Vacancy::factory()->active()->create(['parent_profile_id' => $parent->parentProfile->id]);
    }

    public function test_only_active_non_expired_are_listed(): void
    {
        $ok = $this->make();
        $this->make(['expires_at' => now()->subDay()]);
        Vacancy::factory()->create(['parent_profile_id' => $ok->parent_profile_id]); // pending

        $this->assertSame([$ok->id], $this->ids());
    }

    public function test_filters(): void
    {
        $tashkent = Region::where('slug', 'tashkent-city')->first();
        $other = Region::where('id', '!=', $tashkent->id)->first();

        $a = $this->make(['region_id' => $tashkent->id, 'work_format' => 'hourly', 'salary_min' => 50_000, 'salary_max' => null, 'salary_unit' => 'hour', 'is_urgent' => true, 'children_ages' => [1, 5], 'languages' => ['ru']]);
        $b = $this->make(['region_id' => $other->id, 'work_format' => 'full_time', 'salary_min' => 5_000_000, 'salary_max' => 6_000_000, 'live_in' => true, 'children_ages' => [10]]);

        $this->assertSame([$a->id], $this->ids('?region=tashkent-city'));
        $this->assertSame([$b->id], $this->ids('?work_format=full_time'));
        $this->assertSame([$a->id], $this->ids('?is_urgent=1'));
        $this->assertSame([$b->id], $this->ids('?live_in=1'));
        $this->assertSame([$a->id], $this->ids('?children_age=5'));
        $this->assertSame([$a->id], $this->ids('?language=ru'));
        $this->assertSame([$b->id], $this->ids('?salary_min=5500000'));
        $this->assertSame([], $this->ids('?salary_min=7000000'));
    }

    public function test_salary_desc_sort(): void
    {
        $low = $this->make(['salary_max' => 2_000_000]);
        $high = $this->make(['salary_max' => 9_000_000]);

        $this->assertSame([$high->id, $low->id], $this->ids('?sort=salary_desc'));
    }

    public function test_public_payload_hides_parent_contacts(): void
    {
        $parent = $this->parentUser();
        $vacancy = $this->vacancyOf($parent);

        $list = $this->getJson('/api/v1/vacancies')->assertOk()->assertJsonPath('data.0.parent.name', 'Aziza R.');
        $show = $this->getJson('/api/v1/vacancies/'.$vacancy->id)->assertOk()->assertJsonPath('data.parent.name', 'Aziza R.');

        foreach ([$list->getContent(), $show->getContent()] as $body) {
            $this->assertStringNotContainsString($parent->phone, $body);
            $this->assertStringNotContainsString('Rahimova', $body);
            $this->assertStringNotContainsString('phone', $body);
        }
        $this->assertSame(1, $vacancy->fresh()->views_count);
    }

    public function test_pending_vacancy_is_not_public(): void
    {
        $pending = Vacancy::factory()->create(['parent_profile_id' => $this->parentUser()->parentProfile->id]);

        $this->getJson('/api/v1/vacancies/'.$pending->id)->assertNotFound();
    }
}
