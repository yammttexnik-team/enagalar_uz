<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'feature', 'model', 'input_tokens', 'output_tokens', 'status'])]
class AiRequest extends Model {}
