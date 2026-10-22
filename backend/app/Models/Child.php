<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['parent_profile_id', 'birth_year', 'gender', 'special_needs'])]
class Child extends Model
{
    public function parentProfile(): BelongsTo
    {
        return $this->belongsTo(ParentProfile::class);
    }
}
