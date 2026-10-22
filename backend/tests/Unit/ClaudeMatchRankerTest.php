<?php

namespace Tests\Unit;

use App\Models\AiRequest;
use App\Services\Matching\ClaudeMatchRanker;
use App\Services\Matching\MatchPayload;
use Tests\TestCase;

class ClaudeMatchRankerTest extends TestCase
{
    private function ranker(array $response): ClaudeMatchRanker
    {
        return new class($response) extends ClaudeMatchRanker
        {
            public ?string $sentUserContent = null;

            public function __construct(private array $response) {}

            protected function callApi(string $system, string $userContent, string $model): array
            {
                $this->sentUserContent = $userContent;

                return $this->response;
            }
        };
    }

    private function candidates(): array
    {
        return [['id' => 1], ['id' => 2], ['id' => 3]];
    }

    public function test_parses_valid_response_and_drops_unknown_ids(): void
    {
        $text = json_encode(['results' => [
            ['id' => 2, 'score' => 90, 'reason_uz' => 'Bir tumanda', 'reason_ru' => 'Один район'],
            ['id' => 42, 'score' => 99, 'reason_uz' => 'x', 'reason_ru' => 'x'],
            ['id' => 1, 'score' => 150, 'reason_uz' => str_repeat('a', 300), 'reason_ru' => 'y'],
            ['id' => 2, 'score' => 10, 'reason_uz' => 'dup', 'reason_ru' => 'dup'],
        ]]);
        $ranker = $this->ranker(['text' => $text, 'stop_reason' => 'end_turn', 'input_tokens' => 120, 'output_tokens' => 40]);

        $result = $ranker->rank(['id' => 7], $this->candidates(), 'for_vacancy');

        $this->assertSame([1, 2], array_column($result, 'id'));
        $this->assertSame(100, $result[0]['score']);
        $this->assertSame(140, mb_strlen($result[0]['reason_uz']));
        $this->assertSame('ai', $ranker->lastRun()->source);
        $this->assertSame(120, $ranker->lastRun()->inputTokens);
        $this->assertSame(40, $ranker->lastRun()->outputTokens);
        $this->assertStringContainsString('"direction":"for_vacancy"', $ranker->sentUserContent);
    }

    public function test_refusal_and_truncation_fall_back_to_rules(): void
    {
        foreach (['refusal' => 'refused', 'max_tokens' => 'truncated'] as $stop => $status) {
            $ranker = $this->ranker(['text' => '{"results":[]}', 'stop_reason' => $stop, 'input_tokens' => 10, 'output_tokens' => 5]);

            $result = $ranker->rank($this->subject(), [$this->nannyPayload(1)], 'for_vacancy');

            $this->assertSame([1], array_column($result, 'id'));
            $this->assertSame('rules', $ranker->lastRun()->source);
            $this->assertSame($status, $ranker->lastRun()->status);
            $this->assertSame(10, $ranker->lastRun()->inputTokens);
        }
    }

    public function test_garbage_and_all_unknown_ids_fall_back(): void
    {
        $garbage = $this->ranker(['text' => 'not json', 'stop_reason' => 'end_turn', 'input_tokens' => 1, 'output_tokens' => 1]);
        $garbage->rank($this->subject(), [$this->nannyPayload(1)], 'for_vacancy');
        $this->assertSame('invalid', $garbage->lastRun()->status);

        $unknown = $this->ranker(['text' => '{"results":[{"id":9,"score":5,"reason_uz":"a","reason_ru":"b"}]}', 'stop_reason' => 'end_turn', 'input_tokens' => 1, 'output_tokens' => 1]);
        $result = $unknown->rank($this->subject(), [$this->nannyPayload(1)], 'for_vacancy');
        $this->assertSame([1], array_column($result, 'id'));
        $this->assertSame('rules', $unknown->lastRun()->source);
    }

    public function test_clean_strips_contacts(): void
    {
        $this->assertSame('Qo\'ng\'iroq qiling , yozing', str_replace('  ', ' ', MatchPayload::clean('Qo\'ng\'iroq qiling +998 (90) 123-45-67, @abc yozing', 400)));
        $this->assertSame('2 yoshli bola, 9 dan 18 gacha', MatchPayload::clean('2 yoshli bola, 9 dan 18 gacha', 400));
        $this->assertNull(MatchPayload::clean('   ', 400));
        $this->assertSame(0, AiRequest::count());
    }

    private function subject(): array
    {
        return ['id' => 5, 'district' => null, 'work_format' => 'full_time', 'languages' => ['uz'], 'salary' => ['min' => null, 'max' => 4_000_000, 'unit' => 'month'], 'children_ages' => [3], 'live_in' => false, 'badges' => []];
    }

    private function nannyPayload(int $id): array
    {
        return ['id' => $id, 'district' => null, 'experience_years' => 3, 'price' => ['amount' => 3_000_000, 'unit' => 'month'], 'work_formats' => ['full_time'], 'languages' => ['uz'], 'age_groups' => ['preschool'], 'skills' => [], 'live_in' => false, 'is_available' => true, 'badges' => [], 'rating' => 0.0, 'reviews_count' => 0, 'bio' => null];
    }
}
