<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Eng ko'p foydalanuvchi olib kelgan kampaniyalar. */
class TopCampaigns extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Kampaniyalar natijasi';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Campaign::query()->withCount(['users', 'surveyResponses'])->withSum('bonusTransactions as bonus_sum', 'amount'))
            ->defaultSort('users_count', 'desc')
            ->columns([
                TextColumn::make('code')->label('Kod')->badge(),
                TextColumn::make('name')->label('Nomi')->limit(40),
                IconColumn::make('is_active')->label('Faol')->boolean(),
                TextColumn::make('users_count')->label('Ro\'yxatdan o\'tgan')->sortable(),
                TextColumn::make('survey_responses_count')->label('So\'rovnomalar')->sortable(),
                TextColumn::make('bonus_sum')->label('Bonus (so\'m)')->numeric()->default(0),
            ])
            ->recordUrl(fn (Campaign $record) => CampaignResource::getUrl('edit', ['record' => $record]))
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
