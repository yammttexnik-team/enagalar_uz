<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\SurveyResponseStatus;
use App\Filament\Resources\BonusTransactions\Pages\ListBonusTransactions;
use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Resources\Surveys\Pages\EditSurvey;
use App\Filament\Resources\Surveys\Pages\ViewSurvey;
use App\Filament\Resources\Surveys\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Campaign;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GrowthAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::factory()->create()->assignRole(Role::Admin->value);
        $this->actingAs($this->admin);
    }

    public function test_pages_render(): void
    {
        $survey = Survey::first();

        Livewire::test(ListCampaigns::class)->assertSuccessful()->assertCanSeeTableRecords(Campaign::orderByDesc('id')->limit(5)->get());
        Livewire::test(ListBonusTransactions::class)->assertSuccessful();
        Livewire::test(ViewSurvey::class, ['record' => $survey->id])->assertSuccessful();
        Livewire::test(EditSurvey::class, ['record' => $survey->id])->assertSuccessful();
    }

    public function test_create_campaign_uppercases_code(): void
    {
        Livewire::test(CreateCampaign::class)
            ->fillForm(['code' => 'summer_1', 'name' => 'Yoz', 'is_active' => true, 'survey_bonus' => 60000])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('campaigns', ['code' => 'SUMMER_1', 'survey_bonus' => 60000]);

        Livewire::test(CreateCampaign::class)
            ->fillForm(['code' => 'bad code!', 'name' => 'X'])
            ->call('create')
            ->assertHasFormErrors(['code']);
    }

    public function test_manual_bonus_action_adds_and_removes(): void
    {
        $user = User::factory()->create();

        Livewire::test(ViewUser::class, ['record' => $user->id])
            ->callAction('manualBonus', ['direction' => 'add', 'amount' => 25000, 'reason' => 'Sovga'])
            ->assertHasNoActionErrors();
        $this->assertSame(25000, $user->bonusBalance());

        Livewire::test(ViewUser::class, ['record' => $user->id])
            ->callAction('manualBonus', ['direction' => 'remove', 'amount' => 5000, 'reason' => 'Tuzatish']);
        $this->assertSame(20000, $user->bonusBalance());
        $this->assertDatabaseHas('bonus_transactions', ['user_id' => $user->id, 'type' => 'manual', 'created_by' => $this->admin->id, 'description' => 'Tuzatish']);
    }

    public function test_flagged_response_can_be_approved_with_bonus_and_rejected_with_revoke(): void
    {
        $survey = Survey::first();
        $user = User::factory()->create();
        $response = SurveyResponse::create(['survey_id' => $survey->id, 'user_id' => $user->id, 'answers' => [], 'status' => SurveyResponseStatus::Flagged]);

        $component = fn () => Livewire::test(ResponsesRelationManager::class, ['ownerRecord' => $survey, 'pageClass' => ViewSurvey::class]);

        $component()->callTableAction('approve', $response);
        $this->assertSame(50000, $user->bonusBalance());
        $this->assertSame(SurveyResponseStatus::Completed, $response->fresh()->status);

        $component()->callTableAction('reject', $response);
        $this->assertSame(0, $user->bonusBalance());
        $this->assertSame(SurveyResponseStatus::Rejected, $response->fresh()->status);
    }

    public function test_csv_export_downloads(): void
    {
        $survey = Survey::first();
        $user = User::factory()->create();
        SurveyResponse::create(['survey_id' => $survey->id, 'user_id' => $user->id, 'answers' => ['n_age' => 'a2'], 'status' => SurveyResponseStatus::Completed]);

        Livewire::test(ResponsesRelationManager::class, ['ownerRecord' => $survey, 'pageClass' => ViewSurvey::class])
            ->callTableAction('export')
            ->assertFileDownloaded();
    }
}
