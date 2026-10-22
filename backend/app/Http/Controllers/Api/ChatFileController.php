<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Chat faylini imzolangan havola orqali beradi (<img>/<audio> sarlavhasiz ishlashi uchun). */
class ChatFileController extends Controller
{
    public function __invoke(Message $message): StreamedResponse
    {
        $disk = Storage::disk(Message::DISK);
        abort_unless($message->attachment_path && $disk->exists($message->attachment_path), 404);

        return $disk->response($message->attachment_path, null, [
            'Content-Type' => $message->meta['mime'] ?? $disk->mimeType($message->attachment_path),
            'Cache-Control' => 'private, max-age=3600',
        ], 'inline');
    }
}
