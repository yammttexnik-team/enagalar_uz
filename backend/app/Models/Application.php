<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vacancy_id', 'nanny_profile_id', 'message', 'status'])]
class Application extends Model
{
    protected function casts(): array
    {
        return ['status' => ApplicationStatus::class];
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function nannyProfile(): BelongsTo
    {
        return $this->belongsTo(NannyProfile::class);
    }
}
