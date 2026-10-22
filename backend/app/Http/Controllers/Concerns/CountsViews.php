<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait CountsViews
{
    /** Bir ko'ruvchi (foydalanuvchi yoki IP) uchun bir e'londa ko'rishlar oralig'i, soat. */
    private const VIEW_WINDOW_HOURS = 6;

    /** Ko'rishni hisoblaydi: egasi hisobga olinmaydi, bir ko'ruvchi 6 soatda ko'pi bilan bir marta. */
    protected function countView(Request $request, Model $listing, ?int $ownerId): void
    {
        $user = $request->user('sanctum');

        if ($user && $ownerId === $user->id) {
            return;
        }

        $viewer = $user ? 'u'.$user->id : 'ip'.$request->ip();
        $key = 'view:'.$listing->getMorphClass().':'.$listing->getKey().':'.$viewer;

        if (Cache::add($key, 1, now()->addHours(self::VIEW_WINDOW_HOURS))) {
            $listing->increment('views_count');
        }
    }
}
