<?php

namespace App\Http\Controllers\Api;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DocumentUploadRequest;
use App\Http\Requests\Api\IdentityVerificationRequest;
use App\Http\Resources\VerificationDocumentResource;
use App\Models\Badge;
use App\Models\Consent;
use App\Models\Document;
use App\Models\IdentityVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Foydalanuvchi o'z tasdiqlash so'rovlarini yuboradi. Maxfiy ma'lumotlar javobda hech qachon qaytmaydi. */
class MyVerificationController extends Controller
{
    /** 24 soatda yuborilishi mumkin bo'lgan pasport so'rovlari soni. */
    public const MAX_IDENTITY_PER_DAY = 3;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $identity = $user->identityVerifications()->latest('id')->first();
        $documents = $user->documents()->with('badge')->latest('id')->get();
        $held = $user->badges()->get()->keyBy('id');

        $applicable = Badge::where('is_active', true)
            ->where('grant_type', 'manual')
            ->where('slug', '!=', Badge::VERIFIED)
            ->orderBy('sort')->get()
            ->map(function (Badge $badge) use ($held, $documents) {
                $expires = $held->get($badge->id)?->pivot->expires_at;
                $isHeld = $held->has($badge->id) && (! $expires || $expires->isFuture());

                return [
                    'slug' => $badge->slug,
                    'name' => ['uz' => $badge->name_uz, 'ru' => $badge->name_ru],
                    'description' => ['uz' => $badge->description_uz, 'ru' => $badge->description_ru],
                    'validity_days' => $badge->validity_days,
                    'held' => $isHeld,
                    'expires_at' => $held->has($badge->id) ? $expires?->toIso8601String() : null,
                    'has_pending' => $documents->contains(fn (Document $d) => $d->badge_id === $badge->id && $d->isPending()),
                ];
            })->values();

        return response()->json(['data' => [
            'identity' => [
                'status' => $identity?->status->value ?? 'none',
                'rejection_reason' => $identity?->rejection_reason,
                'submitted_at' => $identity?->created_at?->toIso8601String(),
                'verified' => $user->isVerified(),
            ],
            'documents' => VerificationDocumentResource::collection($documents)->resolve(),
            'available_badges' => $applicable,
        ]]);
    }

    public function storeIdentity(IdentityVerificationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isVerified()) {
            return $this->error('Siz allaqachon tasdiqlangansiz.', 409);
        }
        if ($user->identityVerifications()->where('status', VerificationStatus::Pending)->exists()) {
            return $this->error('Oldingi so\'rovingiz hali ko\'rib chiqilmoqda.', 409);
        }
        $recent = $user->identityVerifications()->where('created_at', '>=', now()->subDay())->count();
        if ($recent >= self::MAX_IDENTITY_PER_DAY) {
            return $this->error('Kuniga 3 martadan ko\'p so\'rov yuborib bo\'lmaydi. Keyinroq urinib ko\'ring.', 429);
        }

        $path = $this->storeFile($request->file('photo'), "identity/{$user->id}");

        DB::transaction(function () use ($request, $user, $path) {
            $user->identityVerifications()->create([
                'full_name' => $request->validated('full_name'),
                'passport_series' => $request->validated('passport_series'),
                'passport_number' => $request->validated('passport_number'),
                'pinfl' => $request->validated('pinfl'),
                'birth_date' => $request->validated('birth_date'),
                'document_path' => $path,
                'status' => VerificationStatus::Pending,
            ]);
            $this->recordConsent($request, Consent::PASSPORT);
        });

        return response()->json(['data' => [
            'status' => VerificationStatus::Pending->value,
            'submitted_at' => now()->toIso8601String(),
        ]], 201);
    }

    public function storeDocument(DocumentUploadRequest $request): JsonResponse
    {
        $user = $request->user();
        $badge = Badge::where('slug', $request->validated('badge'))->firstOrFail();

        if ($user->documents()->where('badge_id', $badge->id)->where('status', VerificationStatus::Pending)->exists()) {
            return $this->error('Bu nishon uchun hujjat allaqachon ko\'rib chiqilmoqda.', 409);
        }

        $path = $this->storeFile($request->file('file'), "documents/{$user->id}");

        $document = DB::transaction(function () use ($request, $user, $badge, $path) {
            $document = $user->documents()->create([
                'badge_id' => $badge->id,
                'type' => $badge->slug,
                'path' => $path,
                'issued_at' => $request->validated('issued_at'),
                'status' => VerificationStatus::Pending,
            ]);
            if (in_array($badge->slug, [Badge::MEDICAL, Badge::DISPENSARY_CLEAR], true)) {
                $this->recordConsent($request, Consent::HEALTH);
            }

            return $document;
        });

        return (new VerificationDocumentResource($document->load('badge')))
            ->response()->setStatusCode(201);
    }

    public function destroyDocument(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->user_id === $request->user()->id, 404);

        if (! $document->isPending()) {
            return $this->error('Faqat ko\'rib chiqilayotgan hujjatni o\'chirish mumkin.', 409);
        }

        if ($document->path) {
            Storage::disk(Document::DISK)->delete($document->path);
        }
        $document->delete();

        return response()->json(null, 204);
    }

    private function storeFile($file, string $dir): string
    {
        $name = Str::random(40).'.'.$file->guessExtension();

        return Storage::disk(IdentityVerification::DISK)->putFileAs($dir, $file, $name);
    }

    private function recordConsent(Request $request, string $type): void
    {
        $request->user()->consents()->create([
            'type' => $type,
            'version' => config('enagalar.consent_version', '2026-10'),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'accepted_at' => now(),
        ]);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
