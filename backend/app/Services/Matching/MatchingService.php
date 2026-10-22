<?php

namespace App\Services\Matching;

use App\Models\AiRequest;
use App\Models\MatchResult;
use App\Models\NannyProfile;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/** Ikki tomonlama moslashtirish: nomzod tanlash, anonim payload, saralash, saqlash. */
class MatchingService
{
    public const FEATURE = 'matching';

    public function __construct(private readonly MatchRanker $ranker) {}

    /** E'lon uchun enagalar: yangi bo'lsa saqlangan, aks holda hisoblanadi. */
    public function forVacancy(Vacancy $vacancy, bool $refresh = false): Collection
    {
        $userId = $vacancy->parentProfile?->user_id;

        if (! $this->shouldRecompute(MatchResult::FOR_VACANCY, 'vacancy_id', $vacancy->id, $refresh, $userId)) {
            return $this->stored(MatchResult::FOR_VACANCY, 'vacancy_id', $vacancy->id);
        }

        return $this->computeForVacancy($vacancy);
    }

    /** Enaga uchun e'lonlar. */
    public function forNanny(NannyProfile $profile, bool $refresh = false): Collection
    {
        if (! $this->shouldRecompute(MatchResult::FOR_NANNY, 'nanny_profile_id', $profile->id, $refresh, $profile->user_id)) {
            return $this->stored(MatchResult::FOR_NANNY, 'nanny_profile_id', $profile->id);
        }

        return $this->computeForNanny($profile);
    }

    public function computeForVacancy(Vacancy $vacancy): Collection
    {
        $vacancy->loadMissing(['district', 'parentProfile.user.activeBadges']);
        $subject = MatchPayload::vacancy($vacancy);

        $candidates = $this->candidateNannies($vacancy)->map(fn (NannyProfile $n) => MatchPayload::nanny($n))->all();
        $candidates = $this->preselect($subject, $candidates, MatchResult::FOR_VACANCY);

        return $this->rankAndStore(
            MatchResult::FOR_VACANCY, 'vacancy_id', $vacancy->id, $subject, $candidates,
            $vacancy->parentProfile?->user_id,
            fn (int $id) => ['nanny_profile_id' => $id, 'vacancy_id' => $vacancy->id],
        );
    }

    public function computeForNanny(NannyProfile $profile): Collection
    {
        $profile->loadMissing(['district', 'skills', 'user.activeBadges']);
        $subject = MatchPayload::nanny($profile);

        $candidates = $this->candidateVacancies($profile)->map(fn (Vacancy $v) => MatchPayload::vacancy($v))->all();
        $candidates = $this->preselect($subject, $candidates, MatchResult::FOR_NANNY);

        return $this->rankAndStore(
            MatchResult::FOR_NANNY, 'nanny_profile_id', $profile->id, $subject, $candidates,
            $profile->user_id,
            fn (int $id) => ['vacancy_id' => $id, 'nanny_profile_id' => $profile->id],
        );
    }

    /** SQL bosqichi: faol, bo'sh, bir viloyat, til va narx bo'yicha mos enagalar. */
    public function candidateNannies(Vacancy $vacancy): \Illuminate\Support\Collection
    {
        $query = NannyProfile::query()->active()->withPublic()
            ->where('is_available', true)
            ->where('region_id', $vacancy->region_id);

        if ($languages = array_values($vacancy->languages ?? [])) {
            $query->where(function (Builder $q) use ($languages) {
                foreach ($languages as $lang) {
                    $q->orWhereJsonContains('languages', $lang);
                }
            });
        }

        if ($vacancy->salary_max && $vacancy->salary_unit) {
            $limit = (int) floor($vacancy->salary_max * (float) config('matching.price_tolerance'));
            // Birligi boshqa yoki narxi ko'rsatilmagan enagalar chiqarib tashlanmaydi.
            $query->where(fn (Builder $q) => $q->whereNull('price_amount')
                ->orWhere('price_unit', '!=', $vacancy->salary_unit->value)
                ->orWhere('price_amount', '<=', $limit));
        }

        return $query->orderByDesc('rating_avg')->orderBy('id')
            ->limit((int) config('matching.pool_limit'))->get();
    }

    /** SQL bosqichi (teskari): enaga viloyatidagi faol e'lonlar. */
    public function candidateVacancies(NannyProfile $profile): \Illuminate\Support\Collection
    {
        $query = Vacancy::query()->active()->withPublic()
            ->where('region_id', $profile->region_id);

        if ($languages = array_values($profile->languages ?? [])) {
            $query->where(function (Builder $q) use ($languages) {
                $q->whereNull('languages');
                foreach ($languages as $lang) {
                    $q->orWhereJsonContains('languages', $lang);
                }
            });
        }

        if ($profile->price_amount && $profile->price_unit) {
            $min = $profile->price_amount / (float) config('matching.price_tolerance');
            $query->where(fn (Builder $q) => $q->whereNull('salary_max')
                ->orWhere('salary_unit', '!=', $profile->price_unit->value)
                ->orWhere('salary_max', '>=', (int) floor($min)));
        }

        return $query->orderByDesc('is_urgent')->orderByDesc('published_at')
            ->limit((int) config('matching.pool_limit'))->get();
    }

    /** Qoidalar bilan eng yaxshi N ta nomzodni oldindan tanlash. */
    private function preselect(array $subject, array $candidates, string $direction): array
    {
        $limit = (int) config('matching.candidate_limit');
        if (count($candidates) <= $limit) {
            return $candidates;
        }

        $scores = [];
        foreach ($candidates as $candidate) {
            [$nanny, $vacancy] = $direction === MatchResult::FOR_VACANCY ? [$candidate, $subject] : [$subject, $candidate];
            $scores[$candidate['id']] = RuleBasedMatchRanker::evaluate($nanny, $vacancy)['score'];
        }

        usort($candidates, fn ($a, $b) => [$scores[$b['id']], $a['id']] <=> [$scores[$a['id']], $b['id']]);

        return array_slice($candidates, 0, $limit);
    }

    private function rankAndStore(string $direction, string $column, int $subjectId, array $subject, array $candidates, ?int $userId, \Closure $keys): Collection
    {
        $rows = [];
        $source = MatchResult::SOURCE_RULES;

        if ($candidates !== []) {
            $ranker = $this->rankerFor($userId);
            $ranked = $ranker->rank($subject, $candidates, $direction);
            $run = $ranker instanceof ReportsRun ? $ranker->lastRun() : null;

            if ($run?->calledAi()) {
                AiRequest::create([
                    'user_id' => $userId,
                    'feature' => self::FEATURE,
                    'model' => $run->model,
                    'input_tokens' => $run->inputTokens,
                    'output_tokens' => $run->outputTokens,
                    'status' => $run->status,
                ]);
            }

            $source = $run?->source ?? ($ranker instanceof RuleBasedMatchRanker ? MatchResult::SOURCE_RULES : MatchResult::SOURCE_AI);

            // Himoya: faqat nomzodlar ro'yxatidagi id lar.
            $allowed = array_flip(array_column($candidates, 'id'));
            $now = now();
            foreach ($ranked as $item) {
                if (! isset($allowed[$item['id']])) {
                    continue;
                }
                $rows[] = $keys($item['id']) + [
                    'direction' => $direction,
                    'score' => max(0, min(100, (int) $item['score'])),
                    'reason_uz' => mb_substr((string) $item['reason_uz'], 0, 140),
                    'reason_ru' => mb_substr((string) $item['reason_ru'], 0, 140),
                    'source' => $source,
                    'computed_at' => $now,
                ];
            }
        }

        DB::transaction(function () use ($direction, $column, $subjectId, $rows) {
            MatchResult::where('direction', $direction)->where($column, $subjectId)->delete();
            foreach ($rows as $row) {
                MatchResult::create($row);
            }
        });

        return $this->stored($direction, $column, $subjectId);
    }

    /** Limit tugagan bo'lsa yoki AI o'chiq bo'lsa — qoidalar. */
    private function rankerFor(?int $userId): MatchRanker
    {
        if ($this->ranker instanceof RuleBasedMatchRanker || $this->limitReached($userId)) {
            return app(RuleBasedMatchRanker::class);
        }

        return $this->ranker;
    }

    public function limitReached(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        return AiRequest::where('user_id', $userId)->where('feature', self::FEATURE)
            ->where('created_at', '>=', now()->startOfDay())->count() >= (int) config('services.anthropic.daily_limit');
    }

    private function shouldRecompute(string $direction, string $column, int $subjectId, bool $refresh, ?int $userId): bool
    {
        // refresh faqat limit tugamagan bo'lsa hisobga olinadi.
        if ($refresh && ! $this->limitReached($userId)) {
            return true;
        }

        return ! MatchResult::where('direction', $direction)->where($column, $subjectId)
            ->where('computed_at', '>=', now()->subHours((int) config('matching.fresh_hours')))->exists();
    }

    /** Saqlangan natijalar; hozir ham faol bo'lgan qarshi tomon bilan, ball bo'yicha. */
    private function stored(string $direction, string $column, int $subjectId): Collection
    {
        $query = MatchResult::where('direction', $direction)->where($column, $subjectId);

        if ($direction === MatchResult::FOR_VACANCY) {
            $query->whereHas('nannyProfile', fn ($q) => $q->active())
                ->with(['nannyProfile' => fn ($q) => $q->withPublic()]);
        } else {
            $query->whereHas('vacancy', fn ($q) => $q->active())
                ->with(['vacancy' => fn ($q) => $q->withPublic()]);
        }

        return $query->orderByDesc('score')->orderBy('id')->get();
    }
}
