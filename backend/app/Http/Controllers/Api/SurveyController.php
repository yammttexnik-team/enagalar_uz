<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Services\Survey\SurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function __construct(private SurveyService $surveys) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $lang = $this->lang($request);
        $done = $user->surveyResponses()->pluck('survey_id')->all();

        return response()->json(['data' => Survey::where('is_active', true)->orderBy('id')->get()->map(fn (Survey $s) => [
            'slug' => $s->slug,
            'audience' => $s->audience,
            'title' => $s->title($lang),
            'bonus' => $this->surveys->bonusFor($s, $user),
            'completed' => in_array($s->id, $done, true),
        ])->values()]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $survey = Survey::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $user = $request->user();
        $lang = $this->lang($request);
        $response = $survey->responses()->where('user_id', $user->id)->first();

        if (! $response) {
            $this->surveys->markStarted($survey, $user);
        }

        return response()->json(['data' => [
            'slug' => $survey->slug,
            'audience' => $survey->audience,
            'title' => $survey->title($lang),
            'intro' => $lang === 'ru' ? $survey->intro_ru : $survey->intro_uz,
            'bonus' => $this->surveys->bonusFor($survey, $user),
            'completed' => (bool) $response,
            'status' => $response?->status->value,
            // Javoblar (must_be) mijozga berilmaydi.
            'questions' => collect($survey->questions)->map(fn (array $q) => array_filter([
                'id' => $q['id'],
                'type' => $q['type'],
                'text' => $lang === 'ru' ? $q['text_ru'] : $q['text_uz'],
                'required' => (bool) ($q['required'] ?? false),
                'options' => isset($q['options']) ? collect($q['options'])->map(fn ($o) => [
                    'value' => (string) $o['value'],
                    'label' => $lang === 'ru' ? $o['ru'] : $o['uz'],
                ])->values()->all() : null,
                'min_length' => $q['min_length'] ?? null,
                'min_words' => $q['min_words'] ?? null,
                'max_select' => $q['max_select'] ?? null,
                'min' => $q['min'] ?? null,
                'max' => $q['max'] ?? null,
            ], fn ($v) => $v !== null))->values(),
        ]]);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $survey = Survey::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $user = $request->user();

        if ($survey->responses()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => "Siz bu so'rovnomani allaqachon to'ldirgansiz."], 409);
        }

        $request->validate(['answers' => ['required', 'array']]);
        $answers = $this->surveys->validate($survey, $request->input('answers'));

        $started = $this->surveys->startedAt($survey, $user) ?? now()->timestamp;
        $seconds = max(0, now()->timestamp - $started);

        if ($seconds < (int) config('bonus.survey_min_seconds')) {
            return response()->json(['message' => "Juda tez to'ldirildi. Savollarni diqqat bilan o'qib, qayta yuboring."], 422);
        }

        $response = $this->surveys->submit($survey, $user, $answers, $seconds);

        if (! $response) {
            return response()->json(['message' => "Siz bu so'rovnomani allaqachon to'ldirgansiz."], 409);
        }

        return response()->json(['data' => [
            'status' => $response->status->value,
            'bonus_awarded' => $response->bonus_awarded_at ? $this->surveys->bonusFor($survey, $user) : 0,
            'balance' => $user->bonusBalance(),
        ]], 201);
    }

    private function lang(Request $request): string
    {
        $lang = $request->query('lang') ?: $request->getPreferredLanguage(['uz', 'ru']) ?: $request->user()->locale;

        return $lang === 'ru' ? 'ru' : 'uz';
    }
}
