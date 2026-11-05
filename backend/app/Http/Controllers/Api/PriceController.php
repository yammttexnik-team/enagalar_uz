<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Services\PriceStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceController extends Controller
{
    public function index(Request $request, PriceStats $stats): JsonResponse
    {
        $slug = $request->query('region');
        $slug = is_string($slug) && $slug !== '' ? $slug : null;
        abort_if($slug && ! Region::where('slug', $slug)->exists(), 404);

        return response()->json(['data' => $stats->get($slug)]);
    }
}
