<?php

namespace App\Filament\Resources\Surveys\RelationManagers;

use App\Enums\BonusType;
use App\Enums\SurveyResponseStatus;
use App\Models\BonusTransaction;
use App\Models\SurveyResponse;
use App\Services\Bonus\BonusService;
use App\Services\Survey\SurveyService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = 'Javoblar';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Foydalanuvchi')->searchable()->description(fn (SurveyResponse $r) => $r->user?->phone),
                TextColumn::make('status')->label('Holat')->badge(),
                TextColumn::make('duration_seconds')->label('Vaqt')->formatStateUsing(fn ($state) => $state === null ? '—' : floor($state / 60).' daq '.($state % 60).' s'),
                TextColumn::make('quality_note')->label('Sifat izohi')->wrap()->placeholder('—'),
                TextColumn::make('bonus_awarded_at')->label('Bonus berilgan')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextColumn::make('created_at')->label('Sana')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->label('Holat')->options(SurveyResponseStatus::class)])
            ->headerActions([
                Action::make('export')
                    ->label('CSV yuklab olish')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse => $this->exportCsv()),
            ])
            ->recordActions([
                Action::make('answers')
                    ->label("Javoblarni ko'rish")
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (SurveyResponse $r) => 'Javoblar: '.$r->user?->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Yopish')
                    ->modalContent(fn (SurveyResponse $r) => view('filament.survey-answers', ['rows' => $this->readable($r)])),
                Action::make('approve')
                    ->label('Tasdiqlash va bonus berish')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SurveyResponse $r) => $r->status === SurveyResponseStatus::Flagged)
                    ->action(function (SurveyResponse $record) {
                        $record->update(['status' => SurveyResponseStatus::Completed]);
                        app(SurveyService::class)->awardBonus($record);
                        Notification::make()->title('Tasdiqlandi, bonus berildi')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Rad etish / bonusni bekor qilish')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (SurveyResponse $r) => $r->status !== SurveyResponseStatus::Rejected)
                    ->action(function (SurveyResponse $record) {
                        $record->update(['status' => SurveyResponseStatus::Rejected]);
                        $tx = BonusTransaction::where('user_id', $record->user_id)->where('type', BonusType::Survey)
                            ->where('reference', 'survey:'.$record->survey_id)->first();
                        if ($tx) {
                            app(BonusService::class)->revoke($tx, "So'rovnoma javobi rad etildi", auth()->user());
                        }
                        Notification::make()->title('Rad etildi')->success()->send();
                    }),
                DeleteAction::make()->label("O'chirish (qayta to'ldirishga ruxsat)"),
            ]);
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function readable(SurveyResponse $response): array
    {
        $rows = [];
        foreach ($response->survey->questions as $q) {
            $answer = $response->answers[$q['id']] ?? null;
            $labels = collect($q['options'] ?? [])->pluck('uz', 'value');
            $text = is_array($answer)
                ? collect($answer)->map(fn ($v) => $labels[$v] ?? $v)->implode('; ')
                : (string) ($labels[$answer] ?? $answer ?? '—');
            $rows[] = [$q['text_uz'], $text];
        }

        return $rows;
    }

    private function exportCsv(): StreamedResponse
    {
        $survey = $this->getOwnerRecord();

        return response()->streamDownload(function () use ($survey) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_merge(['ID', 'Ism', 'Telefon', 'Holat', 'Soniya', 'Sana'], collect($survey->questions)->pluck('text_uz')->all()));

            $survey->responses()->with('user')->orderBy('id')->each(function (SurveyResponse $r) use ($out) {
                $answers = collect($this->readable($r))->pluck(1)->all();
                fputcsv($out, array_merge([
                    $r->id, $r->user?->name, $r->user?->phone, $r->status->value, $r->duration_seconds, $r->created_at->format('Y-m-d H:i'),
                ], array_map(fn ($v) => $this->csvSafe((string) $v), $answers)));
            });
            fclose($out);
        }, "sorovnoma-{$survey->slug}-".now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** CSV-formula inyeksiyasidan himoya. */
    private function csvSafe(string $v): string
    {
        return preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;
    }
}
