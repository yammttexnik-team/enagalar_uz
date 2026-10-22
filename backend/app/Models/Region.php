<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name_uz', 'name_uz_cyrl', 'name_ru', 'sort'])]
class Region extends Model
{
    public $timestamps = false;

    public function districts(): HasMany
    {
        return $this->hasMany(District::class)->orderBy('sort');
    }
}
