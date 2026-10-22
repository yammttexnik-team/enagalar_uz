<?php

namespace Database\Factories;

use App\Models\ParentProfile;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ParentProfile> */
class ParentProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'region_id' => fn () => Region::query()->value('id'),
        ];
    }
}
