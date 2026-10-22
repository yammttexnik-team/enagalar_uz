<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slug', 'name_uz', 'name_ru'])]
class Skill extends Model
{
    public $timestamps = false;
}
