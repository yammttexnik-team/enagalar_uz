<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SurveyTest extends TestCase
{
    use RefreshDatabase;

    private Survey $survey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->survey = Survey::create([
            'slug' => 'test',
            'audience' => 'nanny',
            'title_uz' => 'Test',
            'title_ru' => 'Тест',
            'bonus_amount' => 50000,
            'questions' => [
                ['id' => 'q_single', 'type' => 'single', 'text_uz' => 'S', 'text_ru' => 'С', 'required' => true, 'options' => [['value' => 'a', 'uz' => 'A', 'ru' => 'А'], ['value' => 'b', 'uz' => 'B', 'ru' => 'Б']]],
                ['id' => 'q_multi', 'type' => 'multi', 'text_uz' => 'M', 'text_ru' => 'М', 'required' => true, 'max_select' => 2, 'options' => [['value' => 'x', 'uz' => 'X', 'ru' => 'Х'], ['value' => 'y', 'uz' => 'Y', 'ru' => 'У'], ['value' => 'z', 'uz' => 'Z', 'ru' => 'З']]],
                ['id' => 'q_text', 'type' => 'text', 'text_uz' => 'T', 'text_ru' => 'Т', 'required' => true, 'min_length' => 10],
                ['id' => 'q_text2', 'type' => 'text', 'text_uz' => 'T2', 'text_ru' => 'Т2', 'required' => false],
                ['id' => 'q_num', 'type' => 'number', 'text_uz' => 'N', 'text_ru' => 'Н', 'required' => true, 'min' => 0],
                ['id' => 'q_scale', 'type' => 'scale', 'text_uz' => 'Sc', 'text_ru' => 'Ш', 'required' => true, 'min' => 1, 'max' => 5],
                ['id' => 'q_check', 'type' => 'single', 'text_uz' => 'C', 'text_ru' => 'К', 'required' => true, 'must_be' => 'b', 'options' => [['value' => 'a', 'uz' => 'A', 'ru' => 'А'], ['value' => 'b', 'uz' => 'B', 'ru' => 'Б']]],
            ],
        ]);
    }

    private function valid(array $override = []): array
    {
        return $override + [
            'q_single' => 'a',
            'q_multi' => ['x', 'y'],
            'q_text' => 'Bu yetarlicha uzun javob',
            'q_text2' => 'Boshqa mazmunli fikr bildiraman',
            'q_num' => 3000000,
            'q_scale' => 4,
            'q_check' => 'b',
        ];
    }

    /** GET qilib, vaqtni oldinga suradi (min vaqt o'tgandek). */
    private function open(User $user, int $afterSeconds = 600): void
    {
        $this->actingAs($user)->getJson('/api/v1/surveys/test')->assertOk();
        $this->travel($afterSeconds)->seconds();
    }

    private function submit(User $user, array $answers)
    {
        return $this->actingAs($user)->postJson('/api/v1/surveys/test/responses', ['answers' => $answers]);
    }

    public function test_requires_auth_and_hides_checks_from_client(): void
    {
        $this->getJson('/api/v1/surveys/test')->assertUnauthorized();

        $response = $this->actingAs(User::factory()->create())->getJson('/api/v1/surveys/test?lang=ru')->assertOk()
            ->assertJsonPath('data.title', 'Тест')
            ->assertJsonPath('data.bonus', 50000)
            ->assertJsonPath('data.completed', false)
            ->assertJsonPath('data.questions.0.options.0.label', 'А');

        $this->assertStringNotContainsString('must_be', $response->getContent());
    }

    public function test_valid_completion_awards_default_bonus(): void
    {
        $user = User::factory()->create();
        $this->open($user);

        $this->submit($user, $this->valid())->assertCreated()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.bonus_awarded', 50000)
            ->assertJsonPath('data.balance', 50000);

        $this->assertNotNull($user->surveyResponses()->first()->bonus_awarded_at);
        $this->assertDatabaseHas('bonus_transactions', ['user_id' => $user->id, 'reference' => 'survey:'.$this->survey->id, 'type' => 'survey']);
    }

    public function test_bonus_amount_from_active_campaign(): void
    {
        $campaign = Campaign::create(['code' => 'PROMO', 'name' => 'Promo', 'survey_bonus' => 80000]);
        $user = User::factory()->create(['campaign_id' => $campaign->id]);

        $this->actingAs($user)->getJson('/api/v1/surveys/test')->assertJsonPath('data.bonus', 80000);
        $this->travel(600)->seconds();
        $this->submit($user, $this->valid())->assertJsonPath('data.bonus_awarded', 80000);
        $this->assertSame($campaign->id, $user->bonusTransactions()->first()->campaign_id);
    }

    public function test_inactive_campaign_uses_survey_default(): void
    {
        $campaign = Campaign::create(['code' => 'DEAD', 'name' => 'Dead', 'survey_bonus' => 80000, 'is_active' => false]);
        $user = User::factory()->create(['campaign_id' => $campaign->id]);
        $this->open($user);

        $this->submit($user, $this->valid())->assertJsonPath('data.bonus_awarded', 50000);
    }

    public function test_second_response_is_409_and_bonus_not_doubled(): void
    {
        $user = User::factory()->create();
        $this->open($user);
        $this->submit($user, $this->valid())->assertCreated();

        $this->submit($user, $this->valid())->assertStatus(409);

        $this->assertSame(50000, $user->bonusBalance());
        $this->assertSame(1, $user->surveyResponses()->count());
    }

    #[DataProvider('invalidAnswers')]
    public function test_validation_per_question_type(array $override, string $field): void
    {
        $user = User::factory()->create();
        $this->open($user);

        $this->submit($user, $override + $this->valid())->assertUnprocessable()->assertJsonValidationErrors(["answers.$field"]);
        $this->assertSame(0, $user->surveyResponses()->count());
    }

    public static function invalidAnswers(): array
    {
        return [
            'single not in options' => [['q_single' => 'zzz'], 'q_single'],
            'single missing' => [['q_single' => null], 'q_single'],
            'multi empty' => [['q_multi' => []], 'q_multi'],
            'multi too many' => [['q_multi' => ['x', 'y', 'z']], 'q_multi'],
            'multi bad option' => [['q_multi' => ['x', 'nope']], 'q_multi.1'],
            'multi not array' => [['q_multi' => 'x'], 'q_multi'],
            'text too short' => [['q_text' => 'qisqa'], 'q_text'],
            'text missing' => [['q_text' => ''], 'q_text'],
            'number not numeric' => [['q_num' => 'abc'], 'q_num'],
            'number negative' => [['q_num' => -5], 'q_num'],
            'scale out of range' => [['q_scale' => 6], 'q_scale'],
            'scale zero' => [['q_scale' => 0], 'q_scale'],
            'check missing' => [['q_check' => null], 'q_check'],
        ];
    }

    public function test_optional_question_can_be_skipped(): void
    {
        $user = User::factory()->create();
        $this->open($user);
        $answers = $this->valid();
        unset($answers['q_text2']);

        $this->submit($user, $answers)->assertCreated()->assertJsonPath('data.status', 'completed');
    }

    public function test_unknown_answer_keys_are_dropped(): void
    {
        $user = User::factory()->create();
        $this->open($user);

        $this->submit($user, $this->valid(['evil' => 'x']))->assertCreated();

        $this->assertArrayNotHasKey('evil', $user->surveyResponses()->first()->answers);
    }

    public function test_too_fast_submission_is_rejected_and_can_retry(): void
    {
        $user = User::factory()->create();
        $this->open($user, 30);

        $this->submit($user, $this->valid())->assertUnprocessable();
        $this->assertSame(0, $user->surveyResponses()->count());
        $this->assertSame(0, $user->bonusBalance());

        $this->travel(60)->seconds();
        $this->submit($user, $this->valid())->assertCreated();
    }

    public function test_submit_without_opening_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->submit($user, $this->valid())->assertUnprocessable();
    }

    public function test_fast_but_not_instant_is_flagged_without_bonus(): void
    {
        $user = User::factory()->create();
        $this->open($user, 120);

        $this->submit($user, $this->valid())->assertCreated()
            ->assertJsonPath('data.status', 'flagged')
            ->assertJsonPath('data.bonus_awarded', 0);

        $this->assertSame(0, $user->bonusBalance());
        $this->assertStringContainsString('tez', $user->surveyResponses()->first()->quality_note);
    }

    public function test_duplicate_text_answers_are_flagged(): void
    {
        $user = User::factory()->create();
        $this->open($user);

        $this->submit($user, $this->valid(['q_text' => 'bir xil javob yozaman', 'q_text2' => 'Bir xil javob yozaman']))
            ->assertJsonPath('data.status', 'flagged');
        $this->assertSame(0, $user->bonusBalance());
    }

    public function test_wrong_attention_check_is_flagged(): void
    {
        $user = User::factory()->create();
        $this->open($user);

        $this->submit($user, $this->valid(['q_check' => 'a']))->assertJsonPath('data.status', 'flagged');
    }

    public function test_index_lists_surveys_with_completion(): void
    {
        $user = User::factory()->create();
        $this->open($user);
        $this->submit($user, $this->valid());

        $rows = collect($this->actingAs($user)->getJson('/api/v1/surveys')->assertOk()->json('data'));

        $this->assertTrue($rows->firstWhere('slug', 'test')['completed']);
        $this->assertFalse($rows->firstWhere('slug', 'enaga')['completed']);
    }

    public function test_seeded_surveys_have_valid_structure(): void
    {
        foreach (['enaga' => 25, 'ota-ona' => 26] as $slug => $count) {
            $survey = Survey::where('slug', $slug)->firstOrFail();
            $this->assertCount($count, $survey->questions);
            $this->assertSame(50000, $survey->bonus_amount);
            foreach ($survey->questions as $q) {
                $this->assertContains($q['type'], Survey::TYPES);
                if (in_array($q['type'], ['single', 'multi'])) {
                    $this->assertNotEmpty($q['options'], $q['id']);
                }
            }
        }
        $this->assertCount(14, Survey::where('slug', 'enaga')->first()->questions[0]['options']);
    }
}
