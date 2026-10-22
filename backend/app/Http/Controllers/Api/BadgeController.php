<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BadgeResource;
use App\Models\Badge;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BadgeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BadgeResource::collection(Badge::where('is_active', true)->orderBy('sort')->get());
    }
}
