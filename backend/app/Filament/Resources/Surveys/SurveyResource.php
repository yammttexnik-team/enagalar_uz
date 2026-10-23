<?php

namespace App\Filament\Resources\Surveys;

use App\Filament\Resources\Surveys\Pages\CreateSurvey;
use App\Filament\Resources\Surveys\Pages\EditSurvey;
use App\Filament\Resources\Surveys\Pages\ListSurveys;
use App\Filament\Resources\Surveys\Pages\ViewSurvey;
use App\Filament\Resources\Surveys\RelationManagers\ResponsesRelationManager;
use App\Filament\Support\AdminOnly;
use App\Models\Survey;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class SurveyResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Survey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = "So'rovnoma";

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = "So'rovnomalar";

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asosiy')->columns(2)->schema([
                TextInput::make('slug')->label('Kod (havolada)')->required()->alphaDash()->unique(ignoreRecord: true)->helperText('/sorovnoma/<kod>'),
                Select::make('audience')->label('Kim uchun')->required()->options(['nanny' => 'Enagalar', 'parent' => 'Ota-onalar']),
                TextInput::make('title_uz')->label('Sarlavha (o\'zbekcha)')->required(),
                TextInput::make('title_ru')->label('Sarlavha (ruscha)')->required(),
                Textarea::make('intro_uz')->label("Kirish matni / rozilik (o'zbekcha)")->rows(4),
                Textarea::make('intro_ru')->label('Kirish matni / rozilik (ruscha)')->rows(4),
                TextInput::make('bonus_amount')->label('Standart bonus')->numeric()->minValue(0)->required()->default(50000)->suffix("so'm")
                    ->helperText("Foydalanuvchining faol kampaniyasida so'rovnoma bonusi bo'lsa, o'sha beriladi."),
                Toggle::make('is_active')->label('Faol')->default(true),
            ]),
            Section::make('Savollar')->schema([
                Repeater::make('questions')
                    ->label(false)
                    ->addActionLabel("Savol qo'shish")
                    ->collapsible()->collapsed()->cloneable()->reorderable()
                    ->itemLabel(fn (array $state) => ($state['id'] ?? '?').' — '.($state['text_uz'] ?? ''))
                    ->columns(2)
                    ->schema([
                        TextInput::make('id')->label('Kalit (unikal)')->required()->alphaDash(),
                        Select::make('type')->label('Turi')->required()->live()->options([
                            'single' => 'Bitta tanlov', 'multi' => "Ko'p tanlov", 'text' => 'Matn', 'number' => 'Son', 'scale' => 'Shkala (1-5)',
                        ]),
                        Textarea::make('text_uz')->label('Savol (o\'zbekcha)')->required()->rows(2),
                        Textarea::make('text_ru')->label('Savol (ruscha)')->required()->rows(2),
                        Toggle::make('required')->label('Majburiy')->default(true),
                        TextInput::make('min_length')->label('Min. belgi')->numeric()->visible(fn ($get) => $get('type') === 'text'),
                        TextInput::make('min_words')->label("Min. so'z")->numeric()->visible(fn ($get) => $get('type') === 'text'),
                        TextInput::make('max_select')->label('Ko\'pi bilan nechta tanlash')->numeric()->visible(fn ($get) => $get('type') === 'multi'),
                        TextInput::make('min')->label('Eng kichik qiymat')->numeric()->visible(fn ($get) => in_array($get('type'), ['number', 'scale'])),
                        TextInput::make('max')->label('Eng katta qiymat')->numeric()->visible(fn ($get) => in_array($get('type'), ['number', 'scale'])),
                        TextInput::make('must_be')->label("Diqqat savoli: to'g'ri variant kodi")->visible(fn ($get) => $get('type') === 'single')
                            ->helperText("Bo'sh qoldiring. Noto'g'ri javobda bonus ushlab turiladi."),
                        Repeater::make('options')
                            ->label('Variantlar')->addActionLabel("Variant qo'shish")
                            ->visible(fn ($get) => in_array($get('type'), ['single', 'multi']))
                            ->columns(3)->columnSpanFull()->defaultItems(0)
                            ->schema([
                                TextInput::make('value')->label('Kod')->required()->alphaDash(),
                                TextInput::make('uz')->label("O'zbekcha")->required(),
                                TextInput::make('ru')->label('Ruscha')->required(),
                            ]),
                    ])->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title_uz')->label('Sarlavha'),
            TextEntry::make('audience')->label('Kim uchun')->formatStateUsing(fn ($state) => $state === 'nanny' ? 'Enagalar' : 'Ota-onalar'),
            TextEntry::make('responses_count')->label('Javoblar')->state(fn (Survey $r) => $r->responses()->count()),
            Section::make('Javoblar xulosasi')->columnSpanFull()->schema([
                TextEntry::make('summary')->label(false)->html()->state(fn (Survey $r) => new HtmlString(self::summaryHtml($r))),
            ]),
        ]);
    }

    /** Tanlov savollari uchun javoblar soni (faqat rad etilmagan javoblar). */
    public static function summaryHtml(Survey $survey): string
    {
        $responses = $survey->responses()->where('status', '!=', 'rejected')->get();
        $total = $responses->count();
        $html = '';

        foreach ($survey->questions as $q) {
            $html .= '<div style="margin-bottom:1rem"><strong>'.e($q['text_uz']).'</strong>';
            $answers = $responses->map(fn ($r) => $r->answers[$q['id']] ?? null)->filter(fn ($a) => $a !== null);

            if (in_array($q['type'], ['single', 'multi'])) {
                $counts = $answers->flatten()->countBy();
                foreach ($q['options'] ?? [] as $o) {
                    $n = $counts[$o['value']] ?? 0;
                    $pct = $total ? round($n / $total * 100) : 0;
                    $html .= '<div style="font-size:.875rem">'.e($o['uz']).': <strong>'.$n.'</strong> ('.$pct.'%)</div>';
                }
            } elseif (in_array($q['type'], ['number', 'scale'])) {
                $html .= $answers->isEmpty()
                    ? '<div style="font-size:.875rem">Javob yo\'q</div>'
                    : '<div style="font-size:.875rem">Javoblar: '.$answers->count().', o\'rtacha: <strong>'.number_format($answers->avg(), 1, '.', ' ').'</strong>, mediana: <strong>'.number_format($answers->median(), 1, '.', ' ').'</strong></div>';
            } else {
                $html .= '<div style="font-size:.875rem">Matnli javoblar: '.$answers->count().' (to\'liq matn — Javoblar yorlig\'ida / CSV)</div>';
            }
            $html .= '</div>';
        }

        return $html ?: 'Savollar yo\'q';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_uz')->label('Sarlavha')->searchable(),
                TextColumn::make('slug')->label('Kod')->badge()->color('gray'),
                TextColumn::make('audience')->label('Kim uchun')->badge()->formatStateUsing(fn ($state) => $state === 'nanny' ? 'Enagalar' : 'Ota-onalar'),
                TextColumn::make('responses_count')->label('Javoblar')->counts('responses'),
                TextColumn::make('bonus_amount')->label('Bonus')->numeric()->suffix(" so'm"),
                IconColumn::make('is_active')->label('Faol')->boolean(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ResponsesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveys::route('/'),
            'create' => CreateSurvey::route('/create'),
            'view' => ViewSurvey::route('/{record}'),
            'edit' => EditSurvey::route('/{record}/edit'),
        ];
    }
}
