<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'target', 'filters', 'notify'])]
class SavedSearch extends Model
{
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'notify' => 'boolean',
        ];
    }
}
