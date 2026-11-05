<?php

namespace App\Filament\Resources\Articles;

use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Support\AdminOnly;
use App\Models\Article;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ArticleResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'Maqola';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Maqolalar';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asosiy')->columns(2)->schema([
                TextInput::make('slug')->label('Havola (slug)')->required()->alphaDash()->unique(ignoreRecord: true)->helperText('/maqolalar/<slug>'),
                Select::make('status')->label('Holat')->required()->options([Article::DRAFT => 'Qoralama (yashirin)', Article::PUBLISHED => "E'lon qilingan"]),
                DateTimePicker::make('published_at')->label("E'lon vaqti"),
                TextInput::make('topic_key')->label('Mavzu kaliti')->disabled()->dehydrated(false),
            ]),
            Section::make("O'zbekcha")->schema([
                TextInput::make('title_uz')->label('Sarlavha')->required(),
                Textarea::make('excerpt_uz')->label('Qisqacha (meta tavsif)')->required()->rows(2),
                Textarea::make('body_uz')->label('Matn (markdown)')->required()->rows(20),
            ]),
            Section::make('Ruscha')->collapsed()->schema([
                TextInput::make('title_ru')->label('Sarlavha')->required(),
                Textarea::make('excerpt_ru')->label('Qisqacha (meta tavsif)')->required()->rows(2),
                Textarea::make('body_ru')->label('Matn (markdown)')->required()->rows(20),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title_uz')->label('Sarlavha')->searchable()->limit(60),
                TextColumn::make('topic_key')->label('Mavzu')->badge()->color('gray'),
                TextColumn::make('status')->label('Holat')->badge()
                    ->formatStateUsing(fn ($state) => $state === Article::PUBLISHED ? "E'lon qilingan" : 'Qoralama')
                    ->color(fn ($state) => $state === Article::PUBLISHED ? 'success' : 'gray'),
                TextColumn::make('published_at')->label("E'lon vaqti")->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
