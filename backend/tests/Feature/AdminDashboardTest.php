<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Widgets\GrowthStats;
use App\Filament\Widgets\ModerationQueue;
use App\Filament\Widgets\PlatformStats;
use App\Filament\Widgets\RegionsChart;
use App\Filament\Widgets\SignupsChart;
use App\Filament\Widgets\TopCampaigns;
use App\Models\NannyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_sees_all_dashboard_widgets(): void
    {
        $admin = User::factory()->create()->assignRole(Role::Admin->value);
        User::factory()->create()->assignRole(Role::Nanny->value)
            ->nannyProfile()->create(['bio' => 'Tajribali enaga', 'languages' => ['uz'], 'status' => 'pending']);

        // Widget'lar lazy yuklanadi: sahifa ochilishi, so'ng har biri alohida tekshiriladi
        $this->actingAs($admin)->get('/admin')->assertOk();

        Livewire::test(ModerationQueue::class)->assertOk()->assertSee('Moderatsiya navbati')->assertSee('Enaga anketalari');
        Livewire::test(PlatformStats::class)->assertOk()->assertSee('Foydalanuvchilar');
        Livewire::test(GrowthStats::class)->assertOk()->assertSee('Berilgan bonus');
        Livewire::test(SignupsChart::class)->assertOk();
        Livewire::test(RegionsChart::class)->assertOk();
        Livewire::test(TopCampaigns::class)->assertOk()->assertSee('FRIENDS');

        $this->assertSame(1, NannyProfile::where('status', 'pending')->count());
    }

    public function test_moderator_sees_only_moderation_queue(): void
    {
        $moderator = User::factory()->create()->assignRole(Role::Moderator->value);

        $this->actingAs($moderator)->get('/admin')->assertOk();

        $this->assertTrue(ModerationQueue::canView());
        $this->assertFalse(PlatformStats::canView());
        $this->assertFalse(SignupsChart::canView());
        $this->assertFalse(RegionsChart::canView());
        $this->assertFalse(GrowthStats::canView());
        $this->assertFalse(TopCampaigns::canView());
    }
}
