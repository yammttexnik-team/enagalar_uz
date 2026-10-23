<?php

namespace App\Services\Chat;

use App\Enums\MessageType;
use App\Enums\Role;
use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Jobs\SendChatTelegramNotification;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\NannyProfile;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Rating\ConnectionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Chat qoidalari: suhbat boshlash, xabar yuborish, o'qilmaganlar, bildirishnomalar. */
class ChatService
{
    /** Shu sondan kam xabarli suhbatda aloqa ma'lumoti haqida ogohlantiriladi. */
    public const CONTACT_WARNING_LIMIT = 10;

    /** Telegram bildirishnomasi bir suhbat uchun necha daqiqada bir martadan ko'p yuborilmaydi. */
    public const NOTIFY_THROTTLE_MINUTES = 10;

    /** Xabarni tahrirlash muddati (soat). */
    public const EDIT_WINDOW_HOURS = 24;

    /**
     * Suhbat boshlaydi yoki mavjudini qaytaradi.
     *
     * @param  array{nanny_profile_id?: int|null, vacancy_id?: int|null, application_id?: int|null}  $data
     */
    public function start(User $me, array $data): Conversation
    {
        $vacancy = null;

        if (! empty($data['nanny_profile_id'])) {
            abort_unless($me->hasRole(Role::Parent->value), 403, 'Enaga bilan faqat ota-ona suhbat boshlay oladi.');
            $profile = NannyProfile::active()->with('user')->find($data['nanny_profile_id'])
                ?? throw ValidationException::withMessages(['nanny_profile_id' => ['Anketa faol emas yoki topilmadi.']]);
            $other = $profile->user;
        } elseif (! empty($data['vacancy_id'])) {
            abort_unless($me->hasRole(Role::Nanny->value), 403, 'E\'lon egasi bilan faqat enaga suhbat boshlay oladi.');
            $vacancy = Vacancy::active()->with('parentProfile.user')->find($data['vacancy_id'])
                ?? throw ValidationException::withMessages(['vacancy_id' => ['E\'lon faol emas yoki topilmadi.']]);
            $other = $vacancy->parentProfile->user;
        } else {
            $application = Application::with(['vacancy.parentProfile.user', 'nannyProfile.user'])->findOrFail($data['application_id']);
            $owner = $application->vacancy->parentProfile->user;
            $nanny = $application->nannyProfile->user;
            abort_unless(in_array($me->id, [$owner->id, $nanny->id], true), 403, 'Bu ariza sizga tegishli emas.');
            $other = $me->id === $owner->id ? $nanny : $owner;
            $vacancy = $application->vacancy;
        }

        if ($other->id === $me->id) {
            throw ValidationException::withMessages(['user' => ['O\'zingiz bilan suhbat boshlab bo\'lmaydi.']]);
        }

        $conversation = Conversation::between($me, $other, $vacancy);

        if (! $conversation->wasRecentlyCreated) {
            $theirs = $conversation->participants()->whereKey($other->id)->first();
            abort_if($theirs?->pivot->blocked_at, 403, 'Foydalanuvchi sizni bloklagan.');
        }

        return $conversation;
    }

    /** Matn ichida telefon (7+ raqam), havola, t.me yoki @username bormi. */
    public function containsContactInfo(string $text): bool
    {
        return (bool) preg_match('/(?:\d[\s\-().]*){7,}/u', $text)
            || (bool) preg_match('~(?:https?://|www\.|t\.me/|\b[a-z0-9-]+\.(?:com|uz|ru|org|net|me|io|info)\b)~iu', $text)
            || (bool) preg_match('/(?<![\w.])@[a-z0-9_]{3,}/iu', $text);
    }

    /**
     * Xabarni saqlaydi va hodisa/bildirishnomalarni jo'natadi.
     *
     * @param  array{type: string, body?: string|null, duration?: int|null, reply_to_id?: int|null}  $data
     */
    public function send(Conversation $conversation, User $sender, array $data, ?UploadedFile $file = null): Message
    {
        $type = MessageType::from($data['type']);
        $body = isset($data['body']) ? trim((string) $data['body']) : null;
        $meta = [];

        if ($body !== null && $body !== '' && $conversation->messages()->count() < self::CONTACT_WARNING_LIMIT
            && $this->containsContactInfo($body)) {
            $meta['contact_warning'] = true;
        }

        $path = null;
        if ($file) {
            $meta['mime'] = $file->getMimeType() ?: $file->getClientMimeType();
            $meta['size'] = $file->getSize();
            if ($type === MessageType::Voice && ! empty($data['duration'])) {
                $meta['duration'] = (int) $data['duration'];
            }
            $path = $file->store('chat/'.$conversation->id, Message::DISK);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'type' => $type,
            'body' => $body === '' ? null : $body,
            'attachment_path' => $path,
            'meta' => $meta ?: null,
            'reply_to_id' => $data['reply_to_id'] ?? null,
        ]);

        $conversation->unsetRelation('messages');
        $this->afterSend($conversation, $message, $sender);

        // Ikkala tomon ham yozgan bo'lsa — bog'lanish (reyting oqimi uchun).
        try {
            app(ConnectionService::class)->detect($conversation);
        } catch (Throwable $e) {
            report($e);
        }

        return $message;
    }

    /** Yangi xabardan keyingi realtime hodisalar va Telegram bildirishnomasi. */
    private function afterSend(Conversation $conversation, Message $message, User $sender): void
    {
        $conversation->loadMissing('participants');
        $message->setRelation('conversation', $conversation);
        $message->load(['replyTo' => fn ($q) => $q->withTrashed()]);

        $this->dispatch(new MessageSent($message));

        foreach ($conversation->participants as $participant) {
            if ($participant->id === $sender->id) {
                continue;
            }

            $this->broadcastConversationUpdated($conversation, $participant);
            $this->notifyTelegram($conversation, $message, $sender, $participant);
        }
    }

    /** `user.{id}` kanaliga suhbat yangilanishini yuboradi. */
    public function broadcastConversationUpdated(Conversation $conversation, User $recipient): void
    {
        $last = $conversation->messages()->latest('id')->first();

        $this->dispatch(new ConversationUpdated($recipient->id, [
            'conversation_id' => $conversation->id,
            'unread_count' => $this->unreadCount($conversation, $recipient),
            'last_message' => $this->lastMessageData($last, $recipient),
        ]));
    }

    /** @return array{id: int, type: string, preview: string, sender_is_me: bool, created_at: string|null}|null */
    public function lastMessageData(?Message $message, User $viewer): ?array
    {
        if (! $message) {
            return null;
        }

        return [
            'id' => $message->id,
            'type' => $message->type->value,
            'preview' => $message->preview(),
            'sender_is_me' => $message->sender_id === $viewer->id,
            'created_at' => $message->created_at?->toISOString(),
        ];
    }

    /** Foydalanuvchi uchun shu suhbatdagi o'qilmagan xabarlar soni. */
    public function unreadCount(Conversation $conversation, User $user): int
    {
        $lastRead = (int) DB::table('conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->value('last_read_message_id');

        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->where('id', '>', $lastRead)
            ->count();
    }

    /** Barcha suhbatlar bo'yicha o'qilmaganlar yig'indisi (sarlavha belgisi uchun). */
    public function totalUnread(User $user): int
    {
        return DB::table('messages')
            ->join('conversation_participants as cp', fn ($j) => $j
                ->on('cp.conversation_id', '=', 'messages.conversation_id')
                ->where('cp.user_id', $user->id))
            ->whereNull('messages.deleted_at')
            ->where('messages.sender_id', '!=', $user->id)
            ->whereRaw('messages.id > coalesce(cp.last_read_message_id, 0)')
            ->count();
    }

    /** Telegram orqali bildirishnoma: ovozi o'chirilmagan, token bor, 10 daqiqada bir marta. */
    private function notifyTelegram(Conversation $conversation, Message $message, User $sender, User $recipient): void
    {
        if (! $recipient->telegram_id || blank(config('services.telegram.bot_token'))) {
            return;
        }

        if ($recipient->pivot?->muted) {
            return;
        }

        $key = "chat:tg-notified:{$conversation->id}:{$recipient->id}";
        if (! Cache::add($key, true, now()->addMinutes(self::NOTIFY_THROTTLE_MINUTES))) {
            return;
        }

        SendChatTelegramNotification::dispatch($message->id, $recipient->id);
    }

    /** Realtime xatosi (Reverb o'chiq va h.k.) so'rovni buzmasligi uchun. */
    public function dispatch(object $event): void
    {
        try {
            event($event);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
