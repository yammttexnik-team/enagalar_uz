<?php

namespace App\Http\Controllers\Api;

use App\Enums\ImportedListingStatus;
use App\Enums\WorkFormat;
use App\Http\Controllers\Concerns\CountsViews;
use App\Http\Controllers\Controller;
use App\Http\Resources\ImportedListingResource;
use App\Models\ImportedListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/** Telegram kanallardan olingan e'lonlar: ochiq ro'yxat, ko'rish va egasining o'chirish so'rovi. */
class ImportedListingController extends Controller
{
    use CountsViews;

    public function index(Request $request): AnonymousResourceCollection
    {
        $f = $request->validate([
            'type' => ['nullable', Rule::in([ImportedListing::TYPE_VACANCY, ImportedListing::TYPE_NANNY])],
            'region' => ['nullable', 'string', 'exists:regions,slug'],
            'district' => ['nullable', 'string', 'exists:districts,slug'],
            'work_format' => ['nullable', Rule::enum(WorkFormat::class)],
        ]);

        $query = ImportedListing::visible()->with(['region', 'district'])
            ->when($f['type'] ?? null, fn (Builder $q, $v) => $q->where('type', $v))
            ->when($f['region'] ?? null, fn (Builder $q, $v) => $q->whereHas('region', fn ($r) => $r->where('slug', $v)))
            ->when($f['district'] ?? null, fn (Builder $q, $v) => $q->whereHas('district', fn ($r) => $r->where('slug', $v)))
            ->when($f['work_format'] ?? null, fn (Builder $q, $v) => $q->where('work_format', $v))
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        return ImportedListingResource::collection($query->paginate(20)->withQueryString());
    }

    public function show(Request $request, int $id): ImportedListingResource
    {
        $listing = ImportedListing::visible()->with(['region', 'district'])->findOrFail($id);
        $this->countView($request, $listing, null);

        return new ImportedListingResource($listing);
    }

    /** E'lon egasi (yoki boshqa odam) saytdan olib tashlashni so'raydi — darhol yashiriladi. */
    public function removalRequest(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $listing = ImportedListing::visible()->findOrFail($id);
        $listing->update([
            'status' => ImportedListingStatus::Removed,
            'removal_requested_at' => now(),
            'removal_reason' => $data['reason'] ?? null,
            'archive_reason' => 'removal_request',
            'archived_at' => now(),
        ]);

        return response()->json(['message' => "So'rovingiz qabul qilindi. E'lon saytdan olib tashlandi."], 202);
    }
}
