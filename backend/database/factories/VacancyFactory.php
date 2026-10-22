<?php

namespace Database\Factories;

use App\Enums\VacancyStatus;
use App\Models\ParentProfile;
use App\Models\Region;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vacancy> */
class VacancyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parent_profile_id' => ParentProfile::factory(),
            'region_id' => fn () => Region::query()->value('id'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'work_format' => 'full_time',
            'salary_min' => 3_000_000,
            'salary_max' => 4_000_000,
            'salary_unit' => 'month',
            'children_count' => 1,
            'children_ages' => [3],
            'languages' => ['uz'],
            'live_in' => false,
            'is_urgent' => false,
            'status' => VacancyStatus::Pending,
        ];
    }

    /** Faol, muddati o'tmagan e'lon. */
    public function active(): static
    {
        return $this->state(fn () => [
            'status' => VacancyStatus::Active,
            'published_at' => now(),
            'expires_at' => now()->addDays(Vacancy::LIFETIME_DAYS),
        ]);
    }
}
