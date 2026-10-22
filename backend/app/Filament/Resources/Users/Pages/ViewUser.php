<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\Bonus\BonusService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manualBonus')
                ->label("Qo'lda bonus")
                ->icon('heroicon-o-gift')
                ->color('warning')
                ->modalHeading("Qo'lda bonus berish yoki ayirish")
                ->authorize(fn () => (bool) auth()->user()?->isAdmin())
                ->schema([
                    Select::make('direction')->label('Amal')->options(['add' => 'Bonus berish (+)', 'remove' => 'Bonusni ayirish (-)'])->default('add')->required(),
                    TextInput::make('amount')->label("Summa (so'm)")->numeric()->minValue(1)->maxValue(10_000_000)->required(),
                    Textarea::make('reason')->label('Sabab')->required()->maxLength(255),
                ])
                ->action(function (array $data) {
                    /** @var User $user */
                    $user = $this->getRecord();
                    $amount = (int) $data['amount'] * ($data['direction'] === 'remove' ? -1 : 1);
                    app(BonusService::class)->adjust($user, $amount, $data['reason'], auth()->user());
                    $this->refreshFormData([]);
                    Notification::make()->title('Bonus yangilandi')->body('Balans: '.number_format($user->bonusBalance(), 0, '', ' ')." so'm")->success()->send();
                }),
            EditAction::make(),
        ];
    }
}
