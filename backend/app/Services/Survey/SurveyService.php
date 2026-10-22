<?php

namespace App\Services\Survey;

use App\Enums\BonusType;
use App\Enums\SurveyResponseStatus;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\Bonus\BonusService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SurveyService
{
    public function __construct(private BonusService $bonuses) {}

    /** So'rovnoma ochilgan vaqt (birinchi GET); 7 kun saqlanadi. */
    public function markStarted(Survey $survey, User $user): int
    {
        $key = $this->startKey($survey, $user);
        Cache::add($key, now()->timestamp, now()->addDays(7));

        return (int) Cache::get($key);
    }

    public function startedAt(Survey $survey, User $user): ?int
    {
        $v = Cache::get($this->startKey($survey, $user));

        return $v ? (int) $v : null;
    }

    /** Foydalanuvchi uchun bonus miqdori: faol kampaniya qiymati, bo'lmasa so'rovnomaniki. */
    public function bonusFor(Survey $survey, User $user): int
    {
        $campaign = $this->bonuses->campaignFor($user);

        return (int) ($campaign?->survey_bonus ?? $survey->bonus_amount);
    }

    /**
     * Javoblarni savol turlari bo'yicha tekshiradi va tozalangan javoblarni qaytaradi.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(Survey $survey, array $answers): array
    {
        $rules = [];
        $messages = [];
        foreach ($survey->questions as $q) {
            $key = 'answers.'.$q['id'];
            $required = (bool) ($q['required'] ?? false);
            $base = $required ? ['required'] : ['nullable'];
            $values = collect($q['options'] ?? [])->pluck('value')->map(fn ($v) => (string) $v)->all();

            switch ($q['type']) {
                case 'single':
                    $rules[$key] = [...$base, 'string', 'in:'.implode(',', $values)];
                    break;
                case 'multi':
                    $rules[$key] = [...$base, 'array', $required ? 'min:1' : 'min:0'];
                    if (! empty($q['max_select'])) {
                        $rules[$key][] = 'max:'.(int) $q['max_select'];
                    }
                    $rules[$key.'.*'] = ['string', 'distinct', 'in:'.implode(',', $values)];
                    break;
                case 'text':
                    $rules[$key] = [...$base, 'string', 'max:2000'];
                    if (! empty($q['min_length'])) {
                        $rules[$key][] = 'min:'.(int) $q['min_length'];
                    }
                    if (! empty($q['min_words'])) {
                        $words = (int) $q['min_words'];
                        $rules[$key][] = fn ($a, $v, $fail) => count(preg_split('/\s+/u', trim((string) $v), -1, PREG_SPLIT_NO_EMPTY)) >= $words
                            ? null : $fail("Kamida {$words} ta so'z yozing.");
                    }
                    break;
                case 'number':
                    $rules[$key] = [...$base, 'numeric', 'min:'.($q['min'] ?? 0), 'max:'.($q['max'] ?? 1_000_000_000)];
                    break;
                case 'scale':
                    $rules[$key] = [...$base, 'integer', 'min:'.($q['min'] ?? 1), 'max:'.($q['max'] ?? 5)];
                    break;
            }
        }

        $validated = Validator::make(['answers' => $answers], $rules, [
            'required' => 'Bu savolga javob bering.',
        ])->validate();

        $clean = [];
        foreach ($survey->questions as $q) {
            $value = $validated['answers'][$q['id']] ?? null;
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $clean[$q['id']] = match ($q['type']) {
                'text' => trim($value),
                'number' => $value + 0,
                'scale' => (int) $value,
                'multi' => array_values($value),
                default => $value,
            };
        }

        return $clean;
    }

    /**
     * Sifat tekshiruvi. Qaytaradi: [status, izoh].
     *
     * @param  array<string, mixed>  $answers
     * @return array{0: SurveyResponseStatus, 1: ?string}
     */
    public function assess(Survey $survey, array $answers, int $seconds): array
    {
        $notes = [];

        foreach ($survey->questions as $q) {
            if (isset($q['must_be']) && ($answers[$q['id']] ?? null) !== $q['must_be']) {
                $notes[] = 'Diqqat savoli noto\'g\'ri';
            }
        }

        if ($seconds < (int) config('bonus.survey_flag_seconds')) {
            $notes[] = "Juda tez to'ldirilgan ({$seconds} s)";
        }

        $texts = collect($survey->questions)->where('type', 'text')
            ->map(fn ($q) => isset($answers[$q['id']]) ? mb_strtolower(trim($answers[$q['id']])) : null)
            ->filter()->values();

        if ($texts->count() >= 2) {
            $counts = $texts->countBy();
            $bad = $texts->filter(fn ($t) => $counts[$t] > 1 || mb_strlen($t) < (int) config('bonus.survey_min_text_chars'))->count();
            if ($bad / $texts->count() >= 0.5) {
                $notes[] = 'Matnli javoblar bir xil yoki juda qisqa';
            }
        }

        return $notes
            ? [SurveyResponseStatus::Flagged, implode('; ', array_unique($notes))]
            : [SurveyResponseStatus::Completed, null];
    }

    /**
     * Javobni saqlaydi. Takror bo'lsa null (409). Bonus faqat to'liq va toza javobga avtomatik beriladi.
     */
    public function submit(Survey $survey, User $user, array $answers, int $seconds): ?SurveyResponse
    {
        [$status, $note] = $this->assess($survey, $answers, $seconds);

        try {
            $response = SurveyResponse::create([
                'survey_id' => $survey->id,
                'user_id' => $user->id,
                'answers' => $answers,
                'status' => $status,
                'quality_note' => $note,
                'duration_seconds' => $seconds,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        if ($status === SurveyResponseStatus::Completed) {
            $this->awardBonus($response);
        }

        return $response;
    }

    /** Bonus beradi (idempotent). Admin "Tasdiqlash" tugmasi ham shuni chaqiradi. */
    public function awardBonus(SurveyResponse $response): void
    {
        $user = $response->user;
        $survey = $response->survey;
        $campaign = $this->bonuses->campaignFor($user);

        $tx = $this->bonuses->award(
            $user, BonusType::Survey, $this->bonusFor($survey, $user), 'survey:'.$survey->id, $campaign, "So'rovnomani to'ldirdingiz",
        );

        if ($tx && ! $response->bonus_awarded_at) {
            $response->update(['bonus_awarded_at' => now()]);
        }
    }

    private function startKey(Survey $survey, User $user): string
    {
        return "survey-start:{$survey->id}:{$user->id}";
    }
}
