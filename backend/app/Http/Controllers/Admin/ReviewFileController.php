<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\IdentityVerification;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Yopiq diskdagi pasport/hujjat rasmini faqat moderatorga, vaqtinchalik havola orqali ko'rsatadi. */
class ReviewFileController extends Controller
{
    private const MODELS = [
        'identity' => [IdentityVerification::class, 'document_path'],
        'document' => [Document::class, 'path'],
    ];

    public static function url(string $type, int $id): string
    {
        return URL::temporarySignedRoute('admin.review-file', now()->addMinutes(10), compact('type', 'id'));
    }

    public function __invoke(Request $request, string $type, int $id): StreamedResponse
    {
        abort_unless($request->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        abort_unless(isset(self::MODELS[$type]), 404);

        [$model, $column] = self::MODELS[$type];
        $path = $model::findOrFail($id)->{$column};

        abort_unless($path && Storage::disk($model::DISK)->exists($path), 404);

        return Storage::disk($model::DISK)->response($path, headers: [
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
