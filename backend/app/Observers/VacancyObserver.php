<?php

namespace App\Observers;

use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use App\Services\Bonus\ListingRewards;

/** E'lon FAOL bo'lganda (Vacancy::publish() yoki boshqa yo'l) bonus beradi; tez o'chirilsa bekor qiladi. */
class VacancyObserver
{
    public function saved(Vacancy $vacancy): void
    {
        if ($vacancy->status === VacancyStatus::Active && ($vacancy->wasRecentlyCreated || $vacancy->wasChanged('status'))) {
            $this->safely(fn () => app(ListingRewards::class)->vacancyPublished($vacancy));
        }
    }

    public function deleted(Vacancy $vacancy): void
    {
        $this->safely(fn () => app(ListingRewards::class)->vacancyDeleted($vacancy));
    }

    private function safely(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
