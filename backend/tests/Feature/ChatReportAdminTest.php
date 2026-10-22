<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Filament\Resources\Reports\Pages\ViewReport;
use App\Models\Conversation;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class ChatReportAdminTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    private User $admin;

    private User $reporter;

    private User $accused;

    private Report $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::factory()->create()->assignRole(Role::Admin->value);
        $this->reporter = $this->parentUser();
        $this->accused = $this->nannyUser(['status' => 'active']);

        $conversation = Conversation::between($this->reporter, $this->accused);
        $conversation->messages()->create(['sender_id' => $this->accused->id, 'body' => 'Pul yuboring']);
        $this->report = Report::create([
            'reporter_id' => $this->reporter->id,
            'reportable_type' => $conversation->getMorphClass(),
            'reportable_id' => $conversation->id,
            'reason' => 'fraud',
        ]);
    }

    public function test_report_list_and_view_render_messages_and_log_access(): void
    {
        Log::spy();
        $this->actingAs($this->admin);

        $this->get('/admin/reports')->assertOk()->assertSee('Aziza Rahimova');
        $this->get("/admin/reports/{$this->report->id}")->assertOk()->assertSee('Pul yuboring');

        Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => $context['report_id'] === $this->report->id
            && $context['moderator_id'] === $this->admin->id);
    }

    public function test_mark_reviewed_and_block_user(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ViewReport::class, ['record' => $this->report->getRouteKey()])
            ->callAction('block_user')
            ->assertHasNoActionErrors();

        $this->assertSame(UserStatus::Blocked, $this->accused->fresh()->status);
        $this->assertSame(UserStatus::Active, $this->reporter->fresh()->status);
        $this->assertSame('reviewed', $this->report->fresh()->status->value);
        $this->assertSame($this->admin->id, $this->report->fresh()->handled_by);
    }

    public function test_mark_reviewed(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ViewReport::class, ['record' => $this->report->getRouteKey()])
            ->callAction('reviewed')
            ->assertHasNoActionErrors();

        $this->assertSame('reviewed', $this->report->fresh()->status->value);
        $this->assertSame(UserStatus::Active, $this->accused->fresh()->status);
    }
}
