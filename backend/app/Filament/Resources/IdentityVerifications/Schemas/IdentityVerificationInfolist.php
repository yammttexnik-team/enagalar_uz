<?php

namespace App\Filament\Resources\IdentityVerifications\Schemas;

use App\Http\Controllers\Admin\ReviewFileController;
use App\Models\IdentityVerification;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IdentityVerificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Kiritilgan ma\'lumotlar')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')->label('Profildagi ism'),
                        TextEntry::make('user.phone')->label('Telefon'),
                        TextEntry::make('full_name')->label('F.I.Sh. (pasport bo\'yicha)'),
                        TextEntry::make('birth_date')->label('Tug\'ilgan sana'),
                        TextEntry::make('passport')
                            ->label('Pasport seriya va raqami')
                            ->state(fn (IdentityVerification $record) => $record->passport_series.' '.$record->passport_number),
                        TextEntry::make('pinfl')->label('JShShIR')->copyable(),
                        TextEntry::make('status')->label('Holat')->badge(),
                        TextEntry::make('rejection_reason')->label('Rad etish sababi')->placeholder('—'),
                    ]),
                Section::make('Pasport rasmi')
                    ->description('Tekshiruvdan keyin rasm avtomatik o\'chiriladi.')
                    ->schema([
                        ImageEntry::make('document_preview')
                            ->hiddenLabel()
                            ->state(fn (IdentityVerification $record) => $record->document_path
                                ? ReviewFileController::url('identity', $record->id)
                                : null)
                            ->imageHeight(360)
                            ->placeholder('Rasm o\'chirilgan yoki yuklanmagan'),
                    ]),
            ]);
    }
}
