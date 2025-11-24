<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use App\Services\AICreditScoringService;
use App\Services\RepaymentScheduleService;
use App\Helpers\CurrencyHelper;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;

class ViewLoan extends ViewRecord
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('repaymentSchedule')
                ->label('View Repayment Schedule')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->modalHeading(fn () => 'Repayment Schedule - ' . ($this->record->loan_number ?? 'Loan'))
                ->modalDescription(fn () => 'Borrower: ' . ($this->record->borrower->full_name ?? 'N/A'))
                ->modalWidth(MaxWidth::SevenExtraLarge)
                ->modalContent(function () {
                    $scheduleService = new RepaymentScheduleService();
                    $scheduleData = $scheduleService->calculateSchedule($this->record);
                    $currencyHelper = new CurrencyHelper();
                    
                    $summary = $scheduleData['summary'];
                    $schedule = $scheduleData['schedule'];
                    $nextPayment = $scheduleData['next_payment'];
                    
                    return view('filament.components.repayment-schedule-modal', [
                        'loan' => $this->record,
                        'summary' => $summary,
                        'schedule' => $schedule,
                        'nextPayment' => $nextPayment,
                        'currencyHelper' => $currencyHelper,
                    ]);
                })
                ->modalFooterActions(fn (): array => [
                    Actions\Action::make('download_pdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->url(fn () => route('loan.repayment-schedule.pdf', $this->record))
                        ->openUrlInNewTab(),
                    Actions\Action::make('close')
                        ->label('Close')
                        ->color('gray')
                        ->modalHidden()
                        ->close(),
                ]),
                
            Actions\Action::make('aiCreditScore')
                ->label('Run AI Credit Assessment')
                ->icon('heroicon-o-cpu-chip')
                ->color('success')
                ->action('runAICreditScore')
                ->visible(fn() => !$this->record->ai_scored_at && $this->record->loan_status === 'processing'),

            Actions\Action::make('viewAIAssessment')
                ->label('View AI Assessment')
                ->icon('heroicon-o-document-chart-bar')
                ->color('primary')
                ->url(fn() => LoanResource::getUrl('ai-assessment', ['record' => $this->record]))
                ->visible(fn() => $this->record->ai_scored_at),

            Actions\EditAction::make(),
        ];
    }

    public function runAICreditScore(): void
    {
        try {
            $scoringService = new AICreditScoringService();
            $result = $scoringService->assessLoan($this->record);

            if ($result['success']) {
                $this->record->update([
                    'ai_credit_score' => $result['credit_score'],
                    'default_probability' => $result['default_probability'],
                    'risk_factors' => $result['risk_factors'],
                    'ai_recommendation' => $result['recommendation'],
                    'ai_decision_reason' => $result['decision_reason'],
                    'ai_scored_at' => now(),
                ]);

                Notification::make()
                    ->title('AI Credit Assessment Completed!')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('AI Assessment Failed')
                    ->body($result['error'])
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Assessment Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
