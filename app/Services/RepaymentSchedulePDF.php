<?php

namespace App\Services;

use App\Models\Loan;
use App\Helpers\CurrencyHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class RepaymentSchedulePDF
{
    protected RepaymentScheduleService $scheduleService;
    
    public function __construct(RepaymentScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }
    
    /**
     * Generate PDF for loan repayment schedule
     *
     * @param Loan $loan
     * @return Response
     */
    public function generate(Loan $loan): Response
    {
        $scheduleData = $this->scheduleService->calculateSchedule($loan);
        
        $html = $this->buildHtml($loan, $scheduleData);
        
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        $fileName = 'repayment-schedule-' . $loan->loan_number . '.pdf';
        
        return $pdf->download($fileName);
    }
    
    /**
     * Build HTML content for the PDF
     *
     * @param Loan $loan
     * @param array $scheduleData
     * @return string
     */
    private function buildHtml(Loan $loan, array $scheduleData): string
    {
        $summary = $scheduleData['summary'];
        $schedule = $scheduleData['schedule'];
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Loan Repayment Schedule</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    color: #1a1a1a;
                }
                .header p {
                    margin: 5px 0;
                    font-size: 12px;
                    color: #666;
                }
                .loan-details {
                    margin: 20px 0;
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                }
                .loan-details table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .loan-details td {
                    padding: 5px 10px;
                    font-size: 12px;
                }
                .loan-details td:first-child {
                    font-weight: bold;
                    width: 40%;
                }
                .summary-box {
                    margin: 20px 0;
                    background-color: #e8f4f8;
                    padding: 15px;
                    border-left: 4px solid #0066cc;
                }
                .summary-box h3 {
                    margin: 0 0 10px 0;
                    font-size: 14px;
                    color: #0066cc;
                }
                .schedule-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 11px;
                }
                .schedule-table th {
                    background-color: #333;
                    color: white;
                    padding: 10px 5px;
                    text-align: left;
                    font-weight: bold;
                }
                .schedule-table td {
                    padding: 8px 5px;
                    border-bottom: 1px solid #ddd;
                }
                .schedule-table tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .schedule-table tr:hover {
                    background-color: #f0f0f0;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 10px;
                    color: #999;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                .next-payment {
                    background-color: #fff3cd;
                    padding: 10px;
                    border-left: 4px solid #ffc107;
                    margin: 20px 0;
                }
                .next-payment h3 {
                    margin: 0 0 5px 0;
                    font-size: 14px;
                    color: #856404;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Loan Repayment Schedule</h1>
                <p>Generated on ' . now()->format('F d, Y') . '</p>
            </div>
            
            <div class="loan-details">
                <table>
                    <tr>
                        <td>Loan Number:</td>
                        <td>' . htmlspecialchars($loan->loan_number ?? 'N/A') . '</td>
                        <td>Borrower:</td>
                        <td>' . htmlspecialchars($loan->borrower->full_name ?? 'N/A') . '</td>
                    </tr>
                    <tr>
                        <td>Loan Type:</td>
                        <td>' . htmlspecialchars($loan->loan_type->loan_name ?? 'N/A') . '</td>
                        <td>Loan Status:</td>
                        <td>' . htmlspecialchars(ucfirst($loan->loan_status)) . '</td>
                    </tr>
                    <tr>
                        <td>Release Date:</td>
                        <td>' . htmlspecialchars($summary['release_date']) . '</td>
                        <td>Duration:</td>
                        <td>' . htmlspecialchars($summary['duration'] . ' ' . $summary['interest_cycle']) . '</td>
                    </tr>
                </table>
            </div>
            
            <div class="summary-box">
                <h3>Loan Summary</h3>
                <table style="width:100%">
                    <tr>
                        <td><strong>Principal Amount:</strong></td>
                        <td class="text-right">' . CurrencyHelper::formatMoney($summary['principal_amount']) . '</td>
                        <td><strong>Interest Rate:</strong></td>
                        <td class="text-right">' . $summary['interest_rate'] . '%</td>
                    </tr>
                    <tr>
                        <td><strong>Total Interest:</strong></td>
                        <td class="text-right">' . CurrencyHelper::formatMoney($summary['total_interest']) . '</td>
                        <td><strong>Original Total:</strong></td>
                        <td class="text-right"><strong>' . CurrencyHelper::formatMoney($summary['original_total_repayment']) . '</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Total Paid:</strong></td>
                        <td class="text-right" style="color: #16a34a;"><strong>' . CurrencyHelper::formatMoney($summary['total_paid']) . '</strong></td>
                        <td><strong>Current Balance:</strong></td>
                        <td class="text-right" style="color: #dc2626;"><strong>' . CurrencyHelper::formatMoney($summary['current_balance']) . '</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Payments Made:</strong></td>
                        <td class="text-right">' . $summary['payments_made'] . ' / ' . $summary['duration'] . '</td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </div>';
        
        // Add next payment info if available
        if (!empty($scheduleData['next_payment'])) {
            $nextPayment = $scheduleData['next_payment'];
            $html .= '
            <div class="next-payment">
                <h3>Next Payment Due</h3>
                <p>
                    <strong>Date:</strong> ' . $nextPayment['payment_date']->format('F d, Y') . ' | 
                    <strong>Amount:</strong> ' . CurrencyHelper::formatMoney($nextPayment['payment_amount']) . ' | 
                    <strong>Balance After:</strong> ' . CurrencyHelper::formatMoney($nextPayment['balance_after']) . '
                </p>
            </div>';
        }
        
        $html .= '
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Payment Date</th>
                        <th class="text-right">Payment Amount</th>
                        <th class="text-right">Principal</th>
                        <th class="text-right">Interest</th>
                        <th class="text-right">Balance After</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($schedule as $payment) {
            $html .= '
                    <tr>
                        <td class="text-center">' . $payment['installment_number'] . '</td>
                        <td>' . $payment['payment_date']->format('M d, Y') . '</td>
                        <td class="text-right">' . CurrencyHelper::formatMoney($payment['payment_amount']) . '</td>
                        <td class="text-right">' . CurrencyHelper::formatMoney($payment['principal_portion']) . '</td>
                        <td class="text-right">' . CurrencyHelper::formatMoney($payment['interest_portion']) . '</td>
                        <td class="text-right">' . CurrencyHelper::formatMoney($payment['balance_after']) . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p>This is a computer-generated document. No signature is required.</p>
                <p>&copy; ' . now()->year . ' Loan Management System. All rights reserved.</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}
