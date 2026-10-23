<?php

namespace App\Jobs;

use App\Enums\NannyProfileStatus;
use App\Enums\VacancyStatus;
use App\Models\NannyProfile;
use App\Models\Vacancy;
use App\Services\Ai\ModerationReviewer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Moderatsiyadagi (Pending) e'lonni AI bilan oldindan baholaydi. Tasdiqlash/rad etish admin qo'lida. */
class ReviewListingWithAi implements ShouldQueue
{
    use Queueable;

    public const VACANCY = 'vacancy';

    public const NANNY = 'nanny';

    public int $tries = 2;

    public function __construct(public readonly string $type, public readonly int $id) {}

    public function handle(ModerationReviewer $reviewer): void
    {
        $listing = $this->type === self::VACANCY ? Vacancy::find($this->id) : NannyProfile::find($this->id);
        if (! $listing) {
            return;
        }

        // Allaqachon qaror qabul qilingan bo'lsa, baholash kerak emas.
        $pending = $listing->status === VacancyStatus::Pending || $listing->status === NannyProfileStatus::Pending;
        if ($pending) {
            $reviewer->review($listing);
        }
    }

    /** Observer'lar uchun: AI yoqilgan bo'lsa navbatga qo'yadi (tranzaksiya tugagach). */
    public static function dispatchFor(Vacancy|NannyProfile $listing): void
    {
        if (! ModerationReviewer::enabled()) {
            return;
        }

        self::dispatch($listing instanceof Vacancy ? self::VACANCY : self::NANNY, $listing->id)->afterCommit();
    }
}
