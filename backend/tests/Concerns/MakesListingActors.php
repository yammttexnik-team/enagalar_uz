<?php

namespace Tests\Concerns;

use App\Enums\Role;
use App\Models\NannyProfile;
use App\Models\ParentProfile;
use App\Models\Region;
use App\Models\User;

trait MakesListingActors
{
    protected function parentUser(): User
    {
        $user = User::factory()->create(['name' => 'Aziza Rahimova', 'phone' => '+998901112233']);
        $user->assignRole(Role::Parent->value);
        ParentProfile::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    /** @param  array<string, mixed>  $profile */
    protected function nannyUser(array $profile = [], string $name = 'Dilnoza Karimova'): User
    {
        $user = User::factory()->create(['name' => $name, 'phone' => '+998907778899', 'telegram_username' => 'dilnoza_secret']);
        $user->assignRole(Role::Nanny->value);
        NannyProfile::factory()->create($profile + ['user_id' => $user->id]);

        return $user;
    }

    /** Ota-ona uchun to'g'ri e'lon ma'lumotlari. */
    protected function vacancyPayload(array $override = []): array
    {
        return $override + [
            'region_id' => Region::value('id'),
            'title' => 'Ikki yoshli qizaloq uchun enaga kerak',
            'description' => 'Kunduzi soat 9 dan 18 gacha bolaga qarash, sayr va ovqatlantirish.',
            'work_format' => 'full_time',
            'salary_min' => 3_000_000,
            'salary_max' => 4_000_000,
            'salary_unit' => 'month',
            'children_count' => 1,
            'children_ages' => [2],
            'languages' => ['uz'],
        ];
    }
}
