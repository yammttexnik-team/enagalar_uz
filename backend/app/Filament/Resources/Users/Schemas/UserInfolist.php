<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Language;
use App\Enums\Role;
use App\Filament\Support\EnumLabel;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Ism'),
                TextEntry::make('phone')->label('Telefon')->placeholder('—'),
                TextEntry::make('phone_verified_at')->label('Telefon tasdiqlangan')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('telegram_id')->label('Telegram ID')->placeholder('—'),
                TextEntry::make('telegram_username')->label('Telegram')->prefix('@')->placeholder('—'),
                TextEntry::make('email')->label('Email')->placeholder('—'),
                TextEntry::make('email_verified_at')->label('Email tasdiqlangan')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('avatar_path')->label('Rasm (fayl)')->placeholder('—'),
                TextEntry::make('locale')->label('Til')->formatStateUsing(EnumLabel::using(Language::class)),
                TextEntry::make('roles.name')->label('Rollar')->badge()->formatStateUsing(EnumLabel::using(Role::class)),
                TextEntry::make('status')->label('Holat')->badge(),
                TextEntry::make('last_seen_at')->label('Oxirgi faollik')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('bonus_balance')
                    ->label('Bonus balansi')
                    ->state(fn (User $record): string => number_format($record->bonusBalance(), 0, '', ' ')." so'm"),
                TextEntry::make('campaign.code')
                    ->label('Kampaniya')
                    ->badge()
                    ->placeholder('—'),
                TextEntry::make('referredBy.name')
                    ->label('Kim taklif qilgan')
                    ->placeholder('—'),
                TextEntry::make('created_at')->label("Ro'yxatdan o'tgan")->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('updated_at')->label("O'zgartirilgan")->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('deleted_at')->label("O'chirilgan")->dateTime('d.m.Y H:i')
                    ->visible(fn (User $record): bool => $record->trashed()),
            ]);
    }
}
