<?php

namespace App\Filament\Resources\BonusTransactions;

use App\Enums\BonusType;
use App\Filament\Resources\BonusTransactions\Pages\ListBonusTransactions;
use App\Filament\Support\AdminOnly;
use App\Models\BonusTransaction;
use App\Services\Bonus\BonusService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Bonus daftari: faqat ko'rish; tuzatish "Qo'lda bonus" (foydalanuvchi sahifasi) va "Bekor qilish" orqali. */
class BonusTransactionResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = BonusTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'bonus yozuvi';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Bonuslar daftari';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Sana')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Foydalanuvchi')->searchable()->description(fn (BonusTransaction $r) => $r->user?->phone),
                TextColumn::make('type')->label('Turi')->badge(),
                TextColumn::make('amount')->label("Summa (so'm)")->numeric()->sortable()
                    ->color(fn (int $state) => $state < 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : '').number_format($state, 0, '', ' ')),
                TextColumn::make('description')->label('Izoh')->limit(40)->tooltip(fn (BonusTransaction $r) => $r->description)->placeholder('—'),
                TextColumn::make('campaign.code')->label('Kampaniya')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('expires_at')->label('Muddati')->date('d.m.Y')->placeholder('—')->toggleable(),
                TextColumn::make('creator.name')->label('Kim berdi')->placeholder('Tizim')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Turi')->options(BonusType::class),
                SelectFilter::make('campaign_id')->label('Kampaniya')->relationship('campaign', 'code'),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Bekor qilish')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (BonusTransaction $r) => $r->amount > 0
                        && ! BonusTransaction::where('user_id', $r->user_id)->where('type', BonusType::Revoke)->where('reference', 'revoke:'.$r->id)->exists())
                    ->schema([Textarea::make('reason')->label('Sabab')->required()->maxLength(255)])
                    ->action(function (BonusTransaction $record, array $data) {
                        app(BonusService::class)->revoke($record, $data['reason'], auth()->user());
                        Notification::make()->title('Bonus bekor qilindi')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBonusTransactions::route('/')];
    }
}
