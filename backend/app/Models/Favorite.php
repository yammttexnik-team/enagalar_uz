<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'favoritable_type', 'favoritable_id'])]
class Favorite extends Model
{
    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }
}
