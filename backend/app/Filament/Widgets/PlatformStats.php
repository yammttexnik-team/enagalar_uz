<?php

namespace App\Filament\Widgets;

use App\Enums\NannyProfileStatus;
use App\Enums\Role;
use App\Enums\VacancyStatus;
use App\Filament\Resources\NannyProfiles\NannyProfileResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Vacancies\VacancyResource;
use App\Models\Application;
use App\Models\Badge;
use App\Models\Message;
use App\Models\NannyProfile;
use App\Models\User;
use App\Models\Vacancy;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/** Platforma ko'rsatkichlari: foydalanuvchilar, e'lonlar, otkliklar, chat (faqat admin). */
class PlatformStats extends StatsOverviewWidget
{
    use Concerns\DailySeries;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Platforma';

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $usersToday = User::where('created_at', '>=', today())->count();
        $nannies = User::role(Role::Nanny->value)->count();
        $parents = User::role(Role::Parent->value)->count();
        $activeNannies = NannyProfile::where('status', NannyProfileStatus::Active)->count();
        $activeVacancies = Vacancy::where('status', VacancyStatus::Active)->count();
        $applicationsWeek = Application::where('created_at', '>=', now()->subDays(7))->count();
        $messagesToday = Message::where('created_at', '>=', today())->count();
        $onlineNow = User::where('last_seen_at', '>=', now()->subMinutes(5))->count();

        return [
            Stat::make('Foydalanuvchilar', User::count())
                ->description("Bugun: +{$usersToday}")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($this->dailySeries(User::query()))
                ->color('primary')
                ->url(UserResource::getUrl()),
            Stat::make('Enagalar / ota-onalar', "{$nannies} / {$parents}")
                ->description('Rol bo\'yicha')
                ->icon('heroicon-o-users'),
            Stat::make('Faol enaga anketalari', $activeNannies)
                ->description('Ochiq qidiruvda ko\'rinadi')
                ->icon('heroicon-o-heart')
                ->url(NannyProfileResource::getUrl()),
            Stat::make('Faol vakansiyalar', $activeVacancies)
                ->description('Ochiq qidiruvda ko\'rinadi')
                ->icon('heroicon-o-megaphone')
                ->url(VacancyResource::getUrl()),
            Stat::make('Otkliklar (7 kun)', $applicationsWeek)
                ->chart($this->dailySeries(Application::query(), 7))
                ->icon('heroicon-o-paper-airplane'),
            Stat::make('Xabarlar (bugun)', $messagesToday)
                ->chart($this->dailySeries(Message::query(), 7))
                ->icon('heroicon-o-chat-bubble-left-right'),
            Stat::make('Hozir onlayn', $onlineNow)
                ->description('Oxirgi 5 daqiqada faol')
                ->icon('heroicon-o-signal')
                ->color('success'),
            Stat::make('Tasdiqlangan pasportlar', $this->verifiedCount())
                ->description('"Tasdiqlangan" nishoni')
                ->icon('heroicon-o-shield-check'),
        ];
    }

    private function verifiedCount(): int
    {
        return User::whereHas('badges', fn (Builder $q) => $q->where('slug', Badge::VERIFIED))->count();
    }
}
