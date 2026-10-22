<?php

namespace App\Filament\Widgets;

use App\Enums\SurveyResponseStatus;
use App\Filament\Resources\BonusTransactions\BonusTransactionResource;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\AiRequest;
use App\Models\BonusTransaction;
use App\Models\SurveyResponse;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/** Marketing va AI: bonuslar, so'rovnomalar, referal, kampaniyalar, AI so'rovlari (faqat admin). */
class GrowthStats extends StatsOverviewWidget
{
    use Concerns\DailySeries;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Marketing va AI';

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
        $issued = (int) BonusTransaction::where('amount', '>', 0)->sum('amount');
        // Barcha yozuvlar yig'indisi = foydalanuvchilar balansidagi jami bonus (majburiyat)
        $liability = (int) BonusTransaction::sum('amount');
        $surveys = SurveyResponse::where('status', SurveyResponseStatus::Completed)->count();
        $flagged = SurveyResponse::where('status', SurveyResponseStatus::Flagged)->count();
        $fromCampaigns = User::whereNotNull('campaign_id')->count();
        $referred = User::whereNotNull('referred_by_id')->count();
        $aiToday = AiRequest::where('created_at', '>=', today());
        $aiErrors = (clone $aiToday)->where('status', '!=', 'ok')->count();
        $aiTokens = (int) (clone $aiToday)->sum('input_tokens') + (int) (clone $aiToday)->sum('output_tokens');

        return [
            Stat::make('Berilgan bonus', $this->som($issued))
                ->description('Balanslarda: '.$this->som($liability))
                ->icon('heroicon-o-gift')
                ->url(BonusTransactionResource::getUrl()),
            Stat::make('So\'rovnomalar', $surveys)
                ->description($flagged > 0 ? "Shubhali: {$flagged}" : 'Shubhali javob yo\'q')
                ->descriptionColor($flagged > 0 ? 'warning' : 'gray')
                ->chart($this->dailySeries(SurveyResponse::query(), 14))
                ->icon('heroicon-o-clipboard-document-list')
                ->url(SurveyResource::getUrl()),
            Stat::make('Kampaniya / referal orqali', "{$fromCampaigns} / {$referred}")
                ->description('Ro\'yxatdan o\'tganlar')
                ->icon('heroicon-o-share'),
            Stat::make('AI so\'rovlari (bugun)', $aiToday->count())
                ->description("Xato: {$aiErrors} · tokenlar: ".Number::format($aiTokens))
                ->descriptionColor($aiErrors > 0 ? 'danger' : 'gray')
                ->chart($this->dailySeries(AiRequest::query(), 14))
                ->icon('heroicon-o-sparkles'),
        ];
    }

    private function som(int $amount): string
    {
        return Number::format($amount).' so\'m';
    }
}
