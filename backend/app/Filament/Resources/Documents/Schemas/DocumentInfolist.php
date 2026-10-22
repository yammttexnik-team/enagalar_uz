<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Http\Controllers\Admin\ReviewFileController;
use App\Models\Document;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Ma\'lumotlar')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')->label('Foydalanuvchi'),
                        TextEntry::make('user.phone')->label('Telefon'),
                        TextEntry::make('badge.name_uz')->label('So\'ralgan nishon'),
                        TextEntry::make('issued_at')->label('Berilgan sana')->date('d.m.Y')->placeholder('—'),
                        TextEntry::make('status')->label('Holat')->badge(),
                        TextEntry::make('rejection_reason')->label('Rad etish sababi')->placeholder('—'),
                    ]),
                Section::make('Hujjat')
                    ->description('Tekshiruvdan keyin fayl avtomatik o\'chiriladi.')
                    ->schema([
                        ImageEntry::make('document_preview')
                            ->hiddenLabel()
                            ->state(fn (Document $record) => $record->path ? ReviewFileController::url('document', $record->id) : null)
                            ->imageHeight(360)
                            ->placeholder('Fayl o\'chirilgan yoki yuklanmagan'),
                    ]),
            ]);
    }
}
