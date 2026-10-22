<?php

namespace App\Observers;

use App\Enums\NannyProfileStatus;
use App\Models\NannyProfile;
use App\Services\Bonus\ListingRewards;

/** Anketa birinchi marta FAOL bo'lganda (qaysi yo'l bilan bo'lmasin) bonus beradi. */
class NannyProfileObserver
{
    public function saved(NannyProfile $profile): void
    {
        if ($profile->status === NannyProfileStatus::Active && ($profile->wasRecentlyCreated || $profile->wasChanged('status'))) {
            try {
                app(ListingRewards::class)->nannyActivated($profile);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
