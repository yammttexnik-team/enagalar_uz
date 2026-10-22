<?php

namespace Database\Factories;

use App\Enums\NannyProfileStatus;
use App\Models\NannyProfile;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NannyProfile> */
class NannyProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'region_id' => fn () => Region::query()->value('id'),
            'birth_year' => fake()->numberBetween(1970, 2003),
            'bio' => fake()->paragraph(),
            'experience_years' => fake()->numberBetween(0, 15),
            'price_amount' => fake()->numberBetween(20, 80) * 100_000,
            'price_unit' => 'month',
            'work_formats' => ['full_time'],
            'languages' => ['uz', 'ru'],
            'age_groups' => ['toddler'],
            'live_in' => false,
            'free_trial_day' => false,
            'is_available' => true,
            'status' => NannyProfileStatus::Draft,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => NannyProfileStatus::Active, 'published_at' => now()]);
    }
}
