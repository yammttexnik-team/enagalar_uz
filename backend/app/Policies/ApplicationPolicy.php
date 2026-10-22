<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /** Arizaning holatini faqat e'lon egasi o'zgartiradi. */
    public function update(User $user, Application $application): bool
    {
        // E'lon o'chirilgan (soft-delete) bo'lsa vacancy null — rad etamiz.
        return $application->vacancy?->parentProfile?->user_id === $user->id;
    }
}
