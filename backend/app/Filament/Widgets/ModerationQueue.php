<?php

namespace App\Filament\Widgets;

use App\Enums\AiVerdict;
use App\Enums\ImportedListingStatus;
use App\Enums\NannyProfileStatus;
use App\Enums\ReportStatus;
use App\Enums\VacancyStatus;
use App\Enums\VerificationStatus;
use App\Filament\Resources\IdentityVerifications\IdentityVerificationResource;
use App\Filament\Resources\ImportedListings\ImportedListingResource;
use App\Filament\Resources\NannyProfiles\NannyProfileResource;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\Vacancies\VacancyResource;
use App\Models\IdentityVerification;
use App\Models\ImportedListing;
use App\Models\NannyProfile;
use App\Models\Report;
use App\Models\Vacancy;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/** Moderatsiya navbati: kutayotgan ishlar soni (moderator ham ko'radi). */
class ModerationQueue extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Moderatsiya navbati';

    protected ?string $pollingInterval = '30s';

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        return [
            $this->queue('Pasportlar', IdentityVerification::where('status', VerificationStatus::Pending)->count(),
                'heroicon-o-identification', IdentityVerificationResource::getUrl()),
            $this->queue('Enaga anketalari', NannyProfile::where('status', NannyProfileStatus::Pending)->count(),
                'heroicon-o-heart', NannyProfileResource::getUrl(),
                $this->suspicious(NannyProfile::where('status', NannyProfileStatus::Pending))),
            $this->queue('Vakansiyalar', Vacancy::where('status', VacancyStatus::Pending)->count(),
                'heroicon-o-megaphone', VacancyResource::getUrl(),
                $this->suspicious(Vacancy::where('status', VacancyStatus::Pending))),
            $this->queue('Shikoyatlar', Report::where('status', ReportStatus::Open)->count(),
                'heroicon-o-flag', ReportResource::getUrl()),
            $this->queue('Import navbati', ImportedListing::where('status', ImportedListingStatus::Pending)->count(),
                'heroicon-o-arrow-down-tray', ImportedListingResource::getUrl()),
        ];
    }

    /** Moderatsiyadagilar ichida AI "shubhali" (bad + check) deb baholaganlar soni. */
    private function suspicious(Builder $pending): int
    {
        return $pending->whereIn('ai_verdict', [AiVerdict::Bad, AiVerdict::Check])->count();
    }

    private function queue(string $label, int $count, string $icon, string $url, int $suspicious = 0): Stat
    {
        return Stat::make($label, $count)
            ->icon($icon)
            ->description($count > 0
                ? 'Tekshirish kutilmoqda'.($suspicious > 0 ? " · AI shubhali: {$suspicious}" : '')
                : 'Navbat bo\'sh')
            ->color($count > 0 ? 'warning' : 'success')
            ->url($url);
    }
}
