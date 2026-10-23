<?php

namespace App\Filament\Resources\ImportedListings\Schemas;

use App\Enums\AgeGroup;
use App\Enums\Language;
use App\Filament\Resources\ImportedListings\ImportedListingResource;
use App\Filament\Support\EnumLabel;
use App\Models\ImportedListing;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImportedListingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("E'lon (saytda ko'rinadigan, kontaktsiz)")->columns(3)->columnSpanFull()->schema([
                TextEntry::make('type')->label('Turi')->badge()
                    ->formatStateUsing(fn (string $state) => ImportedListingResource::TYPES[$state] ?? $state),
                TextEntry::make('status')->label('Holat')->badge(),
                TextEntry::make('archive_reason')->label('Arxiv sababi')->placeholder('—'),
                TextEntry::make('title')->label('Sarlavha')->columnSpanFull(),
                TextEntry::make('description')->label('Tavsif')->columnSpanFull(),
                TextEntry::make('region.name_uz')->label('Viloyat')->placeholder('—'),
                TextEntry::make('district.name_uz')->label('Tuman')->placeholder('—'),
                TextEntry::make('work_format')->label('Ish formati')->badge()->placeholder('—'),
                TextEntry::make('schedule')->label('Ish jadvali')->placeholder('—'),
                TextEntry::make('price_min')->label('Narx / maosh (dan)')->numeric(thousandsSeparator: ' ')->suffix(" so'm")->placeholder('—'),
                TextEntry::make('price_max')->label('Narx / maosh (gacha)')->numeric(thousandsSeparator: ' ')->suffix(" so'm")->placeholder('—'),
                TextEntry::make('price_unit')->label('Davri')->badge()->placeholder('—'),
                TextEntry::make('children_count')->label('Bolalar soni')->placeholder('—'),
                TextEntry::make('children_ages')->label('Bolalar yoshi')->badge()->formatStateUsing(fn ($state) => $state.' yosh')->placeholder('—'),
                TextEntry::make('age_groups')->label('Yosh guruhlari')->badge()->formatStateUsing(EnumLabel::using(AgeGroup::class))->placeholder('—'),
                TextEntry::make('languages')->label('Tillar')->badge()->formatStateUsing(EnumLabel::using(Language::class))->placeholder('—'),
                TextEntry::make('experience_years')->label('Tajriba (yil)')->placeholder('—'),
                TextEntry::make('person_age')->label('Enaga yoshi')->placeholder('—'),
                IconEntry::make('live_in')->label('Yashab turish bilan')->boolean(),
                IconEntry::make('is_urgent')->label('Shoshilinch')->boolean(),
            ]),
            Section::make('Manba')->columns(3)->columnSpanFull()->schema([
                TextEntry::make('source_url')->label('Post')->url(fn (ImportedListing $record) => $record->source_url, shouldOpenInNewTab: true),
                TextEntry::make('source_posted_at')->label('Joylangan')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('expires_at')->label('Tugaydi')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('published_at')->label('Saytga chiqqan')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('last_checked_at')->label('Oxirgi tekshiruv')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('contacts')->label('Kontaktlar (xesh)')
                    ->state(fn (ImportedListing $record) => count($record->contact_hashes ?? []).' ta'),
            ]),
            Section::make('Asl matn (faqat moderator uchun, saytda ko\'rinmaydi)')->columnSpanFull()->collapsible()->schema([
                TextEntry::make('raw_text')->hiddenLabel()
                    ->state(fn (ImportedListing $record) => $record->raw_text)
                    ->formatStateUsing(fn (?string $state) => nl2br(e((string) $state)))->html(),
            ]),
        ]);
    }
}
