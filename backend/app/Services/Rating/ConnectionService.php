<?php

namespace App\Services\Rating;

use App\Models\Connection;
use App\Models\Conversation;
use App\Models\Review;
use App\Models\User;
use App\Services\Ai\ListingParser;
use App\Services\Telegram\TelegramBot;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Bog'lanish va reyting: aniqlash, "Birga ishladingizmi?" so'rovi, javob va sharh. */
class ConnectionService
{
    /**
     * Suhbatda ikkala tomon ham yozgan bo'lsa, bog'lanishni yozib qo'yadi (bir marta).
     * Ota-ona va enaga aniqlanmasa (masalan, ikki enaga) — null.
     */
    public function detect(Conversation $conversation): ?Connection
    {
        $existing = Connection::query()->where('conversation_id', $conversation->id)->first();
        if ($existing) {
            return $existing;
        }

        $senders = $conversation->messages()->withTrashed()->distinct()->count('sender_id');
        if ($senders < 2) {
            return null;
        }

        $sides = $this->resolveSides($conversation);
        if (! $sides) {
            return null;
        }

        try {
            return Connection::create([
                'conversation_id' => $conversation->id,
                'parent_id' => $sides['parent']->id,
                'nanny_id' => $sides['nanny']->id,
                'connected_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Parallel so'rov oldin yozib bo'lgan.
            return Connection::query()->where('conversation_id', $conversation->id)->first();
        }
    }

    /** @return array{parent: User, nanny: User}|null */
    private function resolveSides(Conversation $conversation): ?array
    {
        $conversation->loadMissing(['participants.nannyProfile', 'participants.parentProfile', 'vacancy.parentProfile']);
        $people = $conversation->participants;
        if ($people->count() !== 2) {
            return null;
        }

        // E'lon orqali boshlangan suhbat: e'lon egasi — ota-ona.
        $ownerId = $conversation->vacancy?->parentProfile?->user_id;
        if ($ownerId && $people->contains('id', $ownerId)) {
            $parent = $people->firstWhere('id', $ownerId);
            $nanny = $people->first(fn (User $u) => $u->id !== $ownerId);

            return $nanny->nannyProfile ? ['parent' => $parent, 'nanny' => $nanny] : null;
        }

        // Aks holda: anketasi bor tomon enaga, ikkinchisi ota-ona profiliga ega bo'lishi kerak.
        $candidates = $people->filter(function (User $u) use ($people) {
            $other = $people->first(fn (User $p) => $p->id !== $u->id);

            return $u->nannyProfile && $other->parentProfile;
        });

        if ($candidates->count() !== 1) {
            return null;
        }

        $nanny = $candidates->first();

        return ['parent' => $people->first(fn (User $u) => $u->id !== $nanny->id), 'nanny' => $nanny];
    }

    /** 3 kun o'tgan, hali so'ralmagan bog'lanishlar bo'yicha ikkala tomondan so'raydi. Necha ta so'ralganini qaytaradi. */
    public function askDue(TelegramBot $bot): int
    {
        $count = 0;

        Connection::query()
            ->whereNull('asked_at')
            ->where('connected_at', '<=', now()->subDays(Connection::ASK_AFTER_DAYS))
            ->with(['parent', 'nanny'])
            ->chunkById(100, function ($connections) use ($bot, &$count) {
                foreach ($connections as $connection) {
                    // Avval belgilanadi: xato bo'lsa ham qayta so'ralmaydi (bir marta).
                    $updated = Connection::query()->whereKey($connection->id)->whereNull('asked_at')->update(['asked_at' => now()]);
                    if (! $updated) {
                        continue;
                    }

                    foreach ([$connection->parent, $connection->nanny] as $user) {
                        $this->sendQuestion($bot, $connection, $user);
                    }
                    $count++;
                }
            });

        return $count;
    }

    private function sendQuestion(TelegramBot $bot, Connection $connection, ?User $user): void
    {
        if (! $user?->telegram_id || blank(config('services.telegram.bot_token'))) {
            return;
        }

        $side = $connection->sideOf($user);
        $other = $connection->userOf(Connection::otherSide($side));
        $ru = $user->locale === 'ru';
        $url = rtrim((string) config('app.frontend_url'), '/').'/kabinet/baholash';

        $text = $ru
            ? "🤝 <b>Вы работали вместе?</b>\nНесколько дней назад вы переписывались с ".e($other?->publicName() ?? '').'. Ответьте, пожалуйста — если вы оба ответите «Да», сможете оценить друг друга.'
            : "🤝 <b>Birga ishladingizmi?</b>\nBir necha kun oldin ".e($other?->publicName() ?? '').' bilan yozishgan edingiz. Javob bering — ikkalangiz «Ha» desangiz, bir-biringizni baholay olasiz.';

        try {
            $bot->sendMessage((int) $user->telegram_id, $text, [
                'inline_keyboard' => [[['text' => $ru ? 'Ответить' : 'Javob berish', 'url' => $url]]],
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** "Birga ishladingizmi?" savoliga javob (bir marta). */
    public function answer(Connection $connection, User $user, bool $worked): Connection
    {
        $side = $this->sideOrFail($connection, $user);

        if (! $connection->asked_at) {
            throw ValidationException::withMessages(['worked' => ['Savol hali berilmagan.']]);
        }

        $updated = Connection::query()->whereKey($connection->id)->whereNull($side.'_worked')->update([
            $side.'_worked' => $worked,
            $side.'_answered_at' => now(),
        ]);

        if (! $updated) {
            throw ValidationException::withMessages(['worked' => ['Siz allaqachon javob bergansiz.']]);
        }

        return $connection->refresh();
    }

    /** Ikkalasi "Ha" degan bo'lsa, ikkinchi tomonni bir marta baholash. */
    public function review(Connection $connection, User $user, int $rating, ?string $comment): Review
    {
        $side = $this->sideOrFail($connection, $user);

        if (! $connection->bothWorked()) {
            throw ValidationException::withMessages(['rating' => ['Baholash uchun ikkalangiz ham "Ha, ishladik" deb javob bergan bo\'lishingiz kerak.']]);
        }

        $targetSide = Connection::otherSide($side);
        $comment = $comment !== null ? trim(ListingParser::stripContacts(trim($comment))) : null;

        $exists = Review::query()->where('connection_id', $connection->id)->where('author_id', $user->id)->exists();
        if ($exists) {
            throw ValidationException::withMessages(['rating' => ['Siz allaqachon baho qo\'ygansiz.']]);
        }

        try {
            return DB::transaction(fn () => Review::create([
                'connection_id' => $connection->id,
                'author_id' => $user->id,
                'target_user_id' => $connection->{$targetSide.'_id'},
                'target_role' => $targetSide,
                'rating' => $rating,
                'text' => $comment === '' ? null : $comment,
                'status' => Review::PUBLISHED,
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['rating' => ['Siz allaqachon baho qo\'ygansiz.']]);
        }
    }

    private function sideOrFail(Connection $connection, User $user): string
    {
        $side = $connection->sideOf($user);
        abort_unless($side !== null, 403, 'Bu bog\'lanish sizga tegishli emas.');

        return $side;
    }
}
