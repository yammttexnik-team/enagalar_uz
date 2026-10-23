<?php

namespace App\Http\Resources;

use App\Models\Badge;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Suhbat (ko'ruvchi nuqtai nazaridan). `participants.activeBadges`, `participants.nannyProfile`,
 * `vacancy`, `latestMessage` yuklangan bo'lishi kerak. Telefon va Telegram hech qachon chiqmaydi.
 *
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $me */
        $me = $request->user();
        $chat = app(ChatService::class);

        $mine = $this->participantFor($me);
        $other = $this->otherFor($me);

        return [
            'id' => $this->id,
            'other' => $other ? $this->otherData($other) : null,
            'vacancy' => $this->vacancy ? ['id' => $this->vacancy->id, 'title' => $this->vacancy->title] : null,
            'last_message' => $chat->lastMessageData($this->latestMessage, $me),
            // Ro'yxat so'rovida withCount bilan keladi, aks holda hisoblanadi.
            'unread_count' => (int) ($this->unread_count ?? $chat->unreadCount($this->resource, $me)),
            'muted' => (bool) $mine?->pivot->muted,
            'blocked_by_me' => (bool) $mine?->pivot->blocked_at,
            'blocked_me' => (bool) $other?->pivot->blocked_at,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function otherData(User $other): array
    {
        $isOwner = $this->vacancy?->parentProfile?->user_id === $other->id;
        $isNanny = ! $isOwner && $other->nannyProfile !== null;

        $words = preg_split('/\s+/u', trim((string) $other->name), -1, PREG_SPLIT_NO_EMPTY);
        $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));

        // Tomonga qarab reyting: enaga anketasidan yoki ota-ona profilidan
        $profile = $isNanny ? $other->nannyProfile : ($other->relationLoaded('parentProfile') ? $other->parentProfile : null);

        return [
            'id' => $other->id,
            'name' => $other->publicName(),
            'initials' => $initials,
            'role' => $isNanny ? 'nanny' : 'parent',
            'rating' => $profile ? (float) $profile->rating_avg : null,
            'reviews_count' => $profile ? (int) $profile->reviews_count : 0,
            'nanny_profile_id' => $isNanny ? $other->nannyProfile->id : null,
            'verified' => $other->activeBadges->contains('slug', Badge::VERIFIED),
            'last_seen_at' => $other->last_seen_at?->toISOString(),
        ];
    }
}
