<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\District;
use App\Models\Region;
use App\Services\Ai\ListingParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesListingActors;
use Tests\TestCase;

class AiListingParseTest extends TestCase
{
    use MakesListingActors, RefreshDatabase;

    /** @var array<int, array{system: string, user: string, model: string, schema: array}> */
    private array $calls = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['services.anthropic.key' => 'sk-test', 'services.anthropic.enabled' => true]);
    }

    /** Claude javobini soxtalashtiradi (ClaudeMatchRanker testlaridagi kabi callApi almashtiriladi). */
    private function fakeClaude(array|\Closure $response): void
    {
        $test = $this;
        $parser = new class($response, $test) extends ListingParser
        {
            public function __construct(private readonly array|\Closure $response, private readonly object $test) {}

            protected function callApi(string $system, string $userContent, string $model, array $schema): array
            {
                $this->test->record(compact('system', 'model', 'schema') + ['user' => $userContent]);
                if ($this->response instanceof \Closure) {
                    return ($this->response)();
                }

                return ['text' => json_encode($this->response, JSON_UNESCAPED_UNICODE), 'stop_reason' => 'end_turn', 'input_tokens' => 300, 'output_tokens' => 120];
            }
        };
        $this->app->instance(ListingParser::class, $parser);
    }

    public function record(array $call): void
    {
        $this->calls[] = $call;
    }

    private const NANNY_TEXT = "Assalomu alaykum! Ismim Malika, 32 yoshdaman. Chilonzor tumanida yashayman. 7 yillik tajriba, \n"
        ."rus va o'zbek tillarini bilaman, ovqat tayyorlayman. Soatiga 40 ming. Tel: +998 90 123-45-67, @malika_enaga, https://t.me/malika";

    public function test_nanny_text_is_parsed_into_profile_fields(): void
    {
        $this->fakeClaude([
            'region' => 'tashkent-city',
            'district' => 'Chilonzor',
            'age' => 32,
            'bio' => '7 yillik tajribaga ega enagaman. Bolalarga ovqat tayyorlayman. Tel +998901234567',
            'experience_years' => 7,
            'price_amount' => 40000,
            'price_unit' => 'hour',
            'work_formats' => ['hourly', 'flying'],
            'languages' => ['ru', 'uz', 'xx'],
            'skills' => ['cooking', 'unknown-skill'],
            'live_in' => false,
        ]);
        $user = $this->nannyUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai/parse-listing', ['type' => 'nanny', 'text' => self::NANNY_TEXT])->assertOk();

        $fields = $response->json('data.fields');
        $region = Region::where('slug', 'tashkent-city')->first();
        $this->assertSame($region->id, $fields['region_id']);
        $this->assertSame(District::where('region_id', $region->id)->where('name_uz', 'Chilonzor')->value('id'), $fields['district_id']);
        $this->assertSame(now()->year - 32, $fields['birth_year']);
        $this->assertSame(7, $fields['experience_years']);
        $this->assertSame(40000, $fields['price_amount']);
        $this->assertSame('hour', $fields['price_unit']);
        $this->assertSame(['hourly'], $fields['work_formats']);
        $this->assertSame(['ru', 'uz'], $fields['languages']);
        $this->assertSame(['cooking'], $fields['skills']);
        $this->assertFalse($fields['live_in']);
        $this->assertStringNotContainsString('998', $fields['bio']);

        // Claude'ga kontaktlar yuborilmadi
        $sent = $this->calls[0]['user'];
        $this->assertStringNotContainsString('123-45-67', $sent);
        $this->assertStringNotContainsString('@malika_enaga', $sent);
        $this->assertStringNotContainsString('t.me', $sent);
        $this->assertStringContainsString('40 ming', $sent);
        $this->assertSame(config('services.anthropic.matching_model'), $this->calls[0]['model']);

        $this->assertDatabaseHas('ai_requests', ['user_id' => $user->id, 'feature' => 'listing_parse', 'status' => 'ok', 'input_tokens' => 300]);
        // Hech narsa saqlanmaydi
        $this->assertNotSame($fields['bio'], $user->nannyProfile->fresh()->bio);
    }

    public function test_vacancy_text_is_parsed_and_invalid_fields_dropped(): void
    {
        $this->fakeClaude([
            'region' => 'samarkand',
            'district' => 'Chilonzor', // boshqa viloyat tumani — tashlanadi
            'title' => 'Ikki yoshli qizaloq uchun enaga kerak',
            'description' => 'Kunduzi qizimizga qarash uchun mehribon enaga kerak. Ish vaqti 9 dan 18 gacha.',
            'work_format' => 'full_time',
            'salary_min' => 5_000_000,
            'salary_max' => 3_000_000,
            'salary_unit' => 'month',
            'children_ages' => [2, 40],
            'is_urgent' => true,
            'starts_at' => 'tomorrow',
        ]);
        Sanctum::actingAs($this->parentUser());

        $fields = $this->postJson('/api/v1/ai/parse-listing', ['type' => 'vacancy', 'text' => 'Samarqandda 2 yoshli qizimga enaga kerak, oyiga 3-5 mln, 90 123 45 67'])
            ->assertOk()->json('data.fields');

        $this->assertSame(Region::where('slug', 'samarkand')->value('id'), $fields['region_id']);
        $this->assertArrayNotHasKey('district_id', $fields);
        $this->assertSame(3_000_000, $fields['salary_min']);
        $this->assertSame(5_000_000, $fields['salary_max']);
        $this->assertSame('full_time', $fields['work_format']);
        $this->assertArrayNotHasKey('children_ages', $fields); // 40 yosh — yaroqsiz
        $this->assertSame(2, $fields['children_count']);
        $this->assertArrayNotHasKey('starts_at', $fields);
        $this->assertTrue($fields['is_urgent']);
        $this->assertStringNotContainsString('45 67', $this->calls[0]['user']);
    }

    public function test_salary_max_without_min_is_kept(): void
    {
        $this->fakeClaude(['salary_max' => 4_000_000, 'salary_unit' => 'month']);
        Sanctum::actingAs($this->parentUser());

        $fields = $this->postJson('/api/v1/ai/parse-listing', ['type' => 'vacancy', 'text' => 'Enaga kerak, oyiga 4 mln gacha beramiz, Toshkent'])
            ->assertOk()->json('data.fields');

        $this->assertSame(['salary_max' => 4_000_000, 'salary_unit' => 'month'], $fields);
    }

    public function test_role_is_required_for_type(): void
    {
        $this->fakeClaude([]);
        Sanctum::actingAs($this->parentUser());
        $this->postJson('/api/v1/ai/parse-listing', ['type' => 'nanny', 'text' => self::NANNY_TEXT])->assertForbidden();

        Sanctum::actingAs($this->nannyUser());
        $this->postJson('/api/v1/ai/parse-listing', ['type' => 'vacancy', 'text' => self::NANNY_TEXT])->assertForbidden();

        $this->assertSame([], $this->calls);
    }

    public function test_validation_and_auth(): void
    {
        $this->postJson('/api/v1/ai/parse-listing', ['type' => 'nanny', 'text' => self::NANNY_TEXT])->assertUnauthorized();

        Sanctum::actingAs($this->nannyUser());
        $this->postJson('/api/v1/ai/parse-listing', ['type' => 'other', 'text' => 'qisqa'])
            ->assertUnprocessable()->assertJsonValidationErrors(['type', 'text']);
        $this->postJson('/api/v1/ai/parse-listing', ['type' => 'nanny', 'text' => str_repeat('a', 4001)])
            ->assertUnprocessable()->assertJsonValidationErrors(['text']);
    }

    public function test_daily_limit_returns_429(): void
    {
        $this->fakeClaude(['experience_years' => 3]);
        $user = $this->nannyUser();
        for ($i = 0; $i < config('services.anthropic.parse_daily_limit'); $i++) {
            AiRequest::create(['user_id' => $user->id, 'feature' => 'listing_parse', 'model' => 'm', 'status' => 'ok']);
        }
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/ai/parse-listing', ['type' => 'nanny', 'text' => self::NANNY_TEXT])
            ->assertStatus(429)->assertJsonPath('reason', 'limit');
        $this->assertSame([], $this->calls);
    }

    public function test_unavailable_without_key_returns_503(): void
    {
        config(['services.anthropic.key' => null]);
        Sanctum::actingAs($this->nannyUser());

        $this->postJson('/api/v1/ai/parse-listing', ['type' => 'nanny', 'text' => self::NANNY_TEXT])
            ->assertStatus(503)->assertJsonPath('reason', 'unavailable');
        $this->assertSame(0, AiRequest::count());
    }

    public function test_api_error_returns_503_and_is_logged(): void
    {
        $this->fakeClaude(fn () => throw new \RuntimeException('network down'));
        $user = $this->nannyUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/ai/parse-listing', ['type' => 'nanny', 'text' => self::NANNY_TEXT])
            ->assertStatus(503)->assertJsonPath('reason', 'unavailable');
        $this->assertDatabaseHas('ai_requests', ['user_id' => $user->id, 'feature' => 'listing_parse', 'status' => 'error']);
    }

    public function test_bad_json_returns_503(): void
    {
        $this->fakeClaude(fn () => ['text' => 'not json', 'stop_reason' => 'end_turn', 'input_tokens' => 10, 'output_tokens' => 5]);
        Sanctum::actingAs($this->nannyUser());
        $this->postJson('/api/v1/ai/parse-listing', ['type' => 'nanny', 'text' => self::NANNY_TEXT])->assertStatus(503);
        $this->assertSame('invalid', AiRequest::latest('id')->value('status'));
    }

    public function test_refusal_returns_503(): void
    {
        Sanctum::actingAs($this->nannyUser());
        $this->fakeClaude(fn () => ['text' => null, 'stop_reason' => 'refusal', 'input_tokens' => 10, 'output_tokens' => 0]);
        $this->postJson('/api/v1/ai/parse-listing', ['type' => 'nanny', 'text' => self::NANNY_TEXT])->assertStatus(503);
        $this->assertSame('refused', AiRequest::latest('id')->value('status'));
    }

    public function test_strip_contacts_keeps_prices(): void
    {
        $out = ListingParser::stripContacts('Narx 3 000 000 so\'m, tel 90 123 45 67 yoki +998(91)1112233, mail a.b@mail.ru, @nanny_uz www.site.uz');

        $this->assertStringContainsString('3 000 000', $out);
        $this->assertStringNotContainsString('123 45 67', $out);
        $this->assertStringNotContainsString('1112233', $out);
        $this->assertStringNotContainsString('mail.ru', $out);
        $this->assertStringNotContainsString('@nanny_uz', $out);
        $this->assertStringNotContainsString('site.uz', $out);
    }

    public function test_district_matches_russian_name(): void
    {
        $region = Region::where('slug', 'tashkent-city')->first();
        $district = ListingParser::findDistrict('Чиланзарский район', null);

        $this->assertSame($region->id, $district?->region_id);
        $this->assertSame('Chilonzor', $district?->name_uz);
    }
}
