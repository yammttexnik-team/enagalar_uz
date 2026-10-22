<?php

namespace App\Jobs;

use App\Enums\NannyProfileStatus;
use App\Models\NannyProfile;
use App\Models\Vacancy;
use App\Services\Matching\MatchingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** E'lon e'lon qilinganda yoki anketa faollashganda moslashtirishni oldindan hisoblaydi. */
class ComputeMatches implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const VACANCY = 'vacancy';

    public const NANNY = 'nanny';

    public int $uniqueFor = 300;

    public function __construct(public readonly string $type, public readonly int $id) {}

    public function uniqueId(): string
    {
        return $this->type.'-'.$this->id;
    }

    public function handle(MatchingService $matching): void
    {
        if ($this->type === self::VACANCY) {
            $vacancy = Vacancy::active()->find($this->id);
            $vacancy && $matching->computeForVacancy($vacancy);

            return;
        }

        $profile = NannyProfile::active()->find($this->id);
        if ($profile && $profile->status === NannyProfileStatus::Active) {
            $matching->computeForNanny($profile);
        }
    }
}
