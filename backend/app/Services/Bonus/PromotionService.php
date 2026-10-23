<?php

namespace App\Services\Bonus;

use App\Models\NannyProfile;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Model;

/**
 * Pullik xizmatlar: TOP e'lon va "Shoshilinch" belgisi. Hozircha faqat bonus balansi bilan to'lanadi.
 * Faol xizmatni qayta sotib olish muddatni joriy tugash vaqtidan uzaytiradi.
 */
class PromotionService
{
    public const SERVICES = ['top', 'urgent'];

    public function __construct(private BonusService $bonuses) {}

    /** Xizmatlar narxi va muddati (UI uchun). */
    public function prices(): array
    {
        return collect(self::SERVICES)->mapWithKeys(fn ($s) => [$s => [
            'price' => $this->price($s),
            'days' => $this->days($s),
        ]])->all();
    }

    public function price(string $service): int
    {
        return (int) config("bonus.prices.$service");
    }

    public function days(string $service): int
    {
        return (int) config("bonus.durations.$service");
    }

    /**
     * Xizmatni sotib oladi. Bir xil e'lon uchun bir kunda bir xil xizmat bir marta yoziladi (idempotent):
     * takroriy so'rov qayta pul yechmaydi va muddatni uzaytirmaydi.
     *
     * @throws InsufficientBonus
     */
    public function buy(User $user, Vacancy|NannyProfile $target, string $service): Model
    {
        $kind = $target instanceof Vacancy ? 'vacancy' : 'nanny';
        $column = $service === 'top' ? 'top_until' : 'urgent_until';
        $reference = "spend:$service:$kind:{$target->id}:".now()->toDateString();

        $this->bonuses->forUser($user, function () use ($user, $target, $service, $kind, $column, $reference) {
            $transaction = $this->bonuses->spend($user, $this->price($service), $reference, $this->description($service, $kind));

            if (! $transaction->wasRecentlyCreated) {
                return;
            }

            $target->refresh();
            $from = $target->{$column}?->isFuture() ? $target->{$column} : now();
            $target->forceFill([$column => $from->copy()->addDays($this->days($service))])->save();
        });

        return $target->refresh();
    }

    private function description(string $service, string $kind): string
    {
        $what = $service === 'top' ? "TOP e'lon" : '"Shoshilinch" belgisi';
        $on = $kind === 'vacancy' ? "e'lon" : 'anketa';

        return "$what, {$this->days($service)} kun ($on)";
    }
}
