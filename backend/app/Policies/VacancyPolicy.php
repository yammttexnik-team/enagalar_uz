<?php

namespace App\Policies;

use App\Enums\VacancyStatus;
use App\Models\User;
use App\Models\Vacancy;

class VacancyPolicy
{
    /** E'lon egasi (ota-ona) ekanini tekshiradi. */
    public function manage(User $user, Vacancy $vacancy): bool
    {
        return $vacancy->parentProfile?->user_id === $user->id;
    }

    /** Yopilgan, topilgan va muddati o'tgan e'lonni tahrirlab bo'lmaydi. */
    public function update(User $user, Vacancy $vacancy): bool
    {
        return $this->manage($user, $vacancy)
            && in_array($vacancy->status, [VacancyStatus::Draft, VacancyStatus::Pending, VacancyStatus::Active, VacancyStatus::Rejected], true);
    }

    public function delete(User $user, Vacancy $vacancy): bool
    {
        return $this->manage($user, $vacancy);
    }

    public function close(User $user, Vacancy $vacancy): bool
    {
        return $this->manage($user, $vacancy)
            && in_array($vacancy->status, [VacancyStatus::Pending, VacancyStatus::Active], true);
    }

    public function viewApplications(User $user, Vacancy $vacancy): bool
    {
        return $this->manage($user, $vacancy);
    }

    public function viewMatches(User $user, Vacancy $vacancy): bool
    {
        return $this->manage($user, $vacancy);
    }
}
