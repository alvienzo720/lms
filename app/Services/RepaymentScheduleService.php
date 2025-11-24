<?php

namespace App\Services;

use App\Models\Loan;
use Carbon\Carbon;

class RepaymentScheduleService
{
    /**
     * Calculate the complete repayment schedule for a loan
     *
     * @param Loan $loan
     * @return array
     */
    public function calculateSchedule(Loan $loan): array
    {
        // Extract loan details
        $principalAmount = floatval($loan->principal_amount);
        $interestRate = floatval($loan->interest_rate);
        $duration = intval($loan->loan_duration);
        $interestCycle = $loan->duration_period; // 'day(s)', 'week(s)', 'month(s)', 'year(s)'
        $releaseDate = Carbon::parse($loan->loan_release_date);
        
        // Calculate original total interest and total repayment
        $totalInterest = ($principalAmount * ($interestRate / 100) * $duration);
        $originalTotalRepayment = $principalAmount + $totalInterest;
        
        // Get current balance from the loan (this reflects payments made)
        $currentBalance = floatval($loan->balance ?? $originalTotalRepayment);
        
        // Calculate how much has been paid
        $totalPaid = $originalTotalRepayment - $currentBalance;
        
        // Calculate original payment per installment
        $originalPaymentPerInstallment = $originalTotalRepayment / $duration;
        
        // Calculate how many payments have been made (approximately)
        $paymentsMade = $totalPaid > 0 ? floor($totalPaid / $originalPaymentPerInstallment) : 0;
        
        // Calculate remaining payments
        $remainingPayments = max(0, $duration - $paymentsMade);
        
        // If there are remaining payments, recalculate the payment amount based on current balance
        $paymentPerInstallment = $remainingPayments > 0 
            ? $currentBalance / $remainingPayments 
            : 0;
        
        // Generate the schedule
        $schedule = [];
        $remainingBalance = $originalTotalRepayment;
        
        for ($i = 1; $i <= $duration; $i++) {
            // Calculate payment date based on interest cycle
            $paymentDate = $this->calculatePaymentDate($releaseDate, $i, $interestCycle);
            
            // Determine if this payment has been made
            $isPaid = $i <= $paymentsMade;
            
            // Calculate payment amount
            $paymentAmount = $isPaid ? $originalPaymentPerInstallment : $paymentPerInstallment;
            
            // Calculate principal and interest portions
            $interestPortion = $totalInterest / $duration;
            $principalPortion = $paymentAmount - $interestPortion;
            
            // Calculate remaining balance after this payment
            $balanceAfterPayment = $remainingBalance - $originalPaymentPerInstallment;
            
            $schedule[] = [
                'installment_number' => $i,
                'payment_date' => $paymentDate,
                'payment_amount' => round($paymentAmount, 2),
                'principal_portion' => round($principalPortion, 2),
                'interest_portion' => round($interestPortion, 2),
                'balance_before' => round($remainingBalance, 2),
                'balance_after' => round(max(0, $balanceAfterPayment), 2),
                'is_paid' => $isPaid,
                'status' => $isPaid ? 'Paid' : ($paymentDate->isPast() ? 'Overdue' : ($paymentDate->isToday() ? 'Due Today' : 'Upcoming')),
            ];
            
            $remainingBalance = $balanceAfterPayment;
        }
        
        return [
            'loan' => $loan,
            'summary' => [
                'principal_amount' => $principalAmount,
                'interest_rate' => $interestRate,
                'total_interest' => round($totalInterest, 2),
                'original_total_repayment' => round($originalTotalRepayment, 2),
                'current_balance' => round($currentBalance, 2),
                'total_paid' => round($totalPaid, 2),
                'duration' => $duration,
                'interest_cycle' => $interestCycle,
                'release_date' => $releaseDate->format('Y-m-d'),
                'original_payment_per_installment' => round($originalPaymentPerInstallment, 2),
                'current_payment_per_installment' => round($paymentPerInstallment, 2),
                'payments_made' => $paymentsMade,
                'remaining_payments' => $remainingPayments,
            ],
            'schedule' => $schedule,
            'next_payment' => $this->getNextPayment($schedule),
        ];
    }
    
    /**
     * Calculate the payment date for a given installment number
     *
     * @param Carbon $releaseDate
     * @param int $installmentNumber
     * @param string $interestCycle
     * @return Carbon
     */
    private function calculatePaymentDate(Carbon $releaseDate, int $installmentNumber, string $interestCycle): Carbon
    {
        $date = $releaseDate->copy();
        
        switch ($interestCycle) {
            case 'day(s)':
                $date->addDays($installmentNumber);
                break;
            case 'week(s)':
                $date->addWeeks($installmentNumber);
                break;
            case 'month(s)':
                $date->addMonths($installmentNumber);
                break;
            case 'year(s)':
                $date->addYears($installmentNumber);
                break;
            default:
                // Default to months if unknown
                $date->addMonths($installmentNumber);
                break;
        }
        
        return $date;
    }
    
    /**
     * Get the next upcoming payment from the schedule
     *
     * @param array $schedule
     * @return array|null
     */
    private function getNextPayment(array $schedule): ?array
    {
        $today = Carbon::today();
        
        foreach ($schedule as $payment) {
            // Skip already paid installments
            if ($payment['is_paid']) {
                continue;
            }
            
            // Return the first unpaid payment
            return $payment;
        }
        
        // If all payments are paid, return null
        return null;
    }
    
    /**
     * Format schedule for display in a table
     *
     * @param array $scheduleData
     * @return array
     */
    public function formatForDisplay(array $scheduleData): array
    {
        $formatted = [];
        
        foreach ($scheduleData['schedule'] as $payment) {
            $formatted[] = [
                'Installment' => $payment['installment_number'],
                'Payment Date' => $payment['payment_date']->format('M d, Y'),
                'Payment Amount' => number_format($payment['payment_amount'], 2),
                'Principal' => number_format($payment['principal_portion'], 2),
                'Interest' => number_format($payment['interest_portion'], 2),
                'Balance After Payment' => number_format($payment['balance_after'], 2),
                'Status' => $payment['status'],
            ];
        }
        
        return $formatted;
    }
}
