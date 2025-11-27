<?php

namespace App\Services;

use App\Models\Repayments;
use App\Models\Loan;
use App\Helpers\CurrencyHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PaymentReceiptPDF
{
    /**
     * Generate PDF receipt for a payment
     *
     * @param Repayments $repayment
     * @return string File path relative to public directory
     */
    public function generate(Repayments $repayment): string
    {
        // Use the correct relationship method and eager load
        $loan = $repayment->loan_number()->with('borrower')->first();
        $borrower = $loan->borrower;
        
        $html = $this->buildHtml($repayment, $loan, $borrower);
        
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        // Create directory structure
        $current_year = date('Y');
        $path = public_path('PAYMENT_RECEIPTS/' . $current_year . '/PDF');
        
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        
        // Generate unique filename
        $file_name = 'receipt-' . Str::random(40) . '.pdf';
        $full_path = $path . '/' . $file_name;
        
        // Save the PDF
        $pdf->save($full_path);
        
        // Return relative path
        return 'PAYMENT_RECEIPTS/' . $current_year . '/PDF/' . $file_name;
    }
    
    /**
     * Generate and download PDF receipt
     *
     * @param Repayments $repayment
     * @return \Illuminate\Http\Response
     */
    public function download(Repayments $repayment)
    {
        // Use the correct relationship method and eager load
        $loan = $repayment->loan_number()->with('borrower')->first();
        $borrower = $loan->borrower;
        
        $html = $this->buildHtml($repayment, $loan, $borrower);
        
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        $fileName = 'payment-receipt-' . $repayment->id . '.pdf';
        
        return $pdf->download($fileName);
    }
    
    /**
     * Build HTML content for the receipt PDF
     *
     * @param Repayments $repayment
     * @param Loan $loan
     * @param $borrower
     * @return string
     */
    private function buildHtml(Repayments $repayment, Loan $loan, $borrower): string
    {
        // Get organization/branch details for the receipt header
        // Priority: Organization name > App name > Default
        $organization_name = config('app.name', 'Loan Management System');
        
        if (auth()->check() && auth()->user()->organization) {
            $org = auth()->user()->organization;
            $organization_name = $org->name ?? $organization_name;
            
            // Include branch name if available
            if (auth()->user()->branch) {
                $branch_name = auth()->user()->branch->name ?? null;
                if ($branch_name) {
                    $organization_name .= ' - ' . $branch_name;
                }
            }
        }
        
        $receipt_number = 'RCP-' . str_pad($repayment->id, 8, '0', STR_PAD_LEFT);
        $receipt_date = $repayment->created_at->format('F d, Y h:i A');
        
        $borrower_name = $borrower->first_name . ' ' . $borrower->last_name;
        $borrower_phone = $borrower->mobile ?? 'N/A';
        $borrower_email = $borrower->email ?? 'N/A';
        
        $loan_number = $loan->loan_number ?? 'N/A';
        $principal_amount = $loan->principal_amount ?? 0;
        $payment_amount = $repayment->payments ?? 0;
        $balance_after = $repayment->balance ?? 0;
        $payment_method = $repayment->payments_method ?? 'N/A';
        $reference_number = $repayment->reference_number ?? 'N/A';
        
        // Format currency
        $formatted_payment = CurrencyHelper::formatMoney($payment_amount);
        $formatted_balance = CurrencyHelper::formatMoney($balance_after);
        $formatted_principal = CurrencyHelper::formatMoney($principal_amount);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Payment Receipt</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 30px;
                    color: #333;
                }
                .receipt-header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 3px solid #0066cc;
                    padding-bottom: 20px;
                }
                .receipt-header h1 {
                    margin: 0;
                    font-size: 32px;
                    color: #0066cc;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                .receipt-header h2 {
                    margin: 5px 0;
                    font-size: 20px;
                    color: #333;
                    font-weight: normal;
                }
                .receipt-header p {
                    margin: 5px 0;
                    font-size: 12px;
                    color: #666;
                }
                .receipt-info {
                    display: table;
                    width: 100%;
                    margin-bottom: 20px;
                }
                .receipt-info-left, .receipt-info-right {
                    display: table-cell;
                    width: 50%;
                    vertical-align: top;
                }
                .info-box {
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 15px;
                }
                .info-box h3 {
                    margin: 0 0 10px 0;
                    font-size: 14px;
                    color: #0066cc;
                    text-transform: uppercase;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 5px;
                }
                .info-row {
                    display: table;
                    width: 100%;
                    margin-bottom: 8px;
                    font-size: 12px;
                }
                .info-label {
                    display: table-cell;
                    font-weight: bold;
                    width: 45%;
                    color: #555;
                }
                .info-value {
                    display: table-cell;
                    width: 55%;
                    color: #333;
                }
                .payment-summary {
                    background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 30px 0;
                    text-align: center;
                }
                .payment-summary h2 {
                    margin: 0 0 15px 0;
                    font-size: 18px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .payment-amount {
                    font-size: 42px;
                    font-weight: bold;
                    margin: 10px 0;
                }
                .balance-section {
                    background-color: #e8f4f8;
                    padding: 15px;
                    border-left: 4px solid #0066cc;
                    margin: 20px 0;
                }
                .balance-section table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .balance-section td {
                    padding: 8px;
                    font-size: 14px;
                }
                .balance-section td:first-child {
                    font-weight: bold;
                    width: 60%;
                }
                .balance-section td:last-child {
                    text-align: right;
                    font-weight: bold;
                    font-size: 16px;
                }
                .footer {
                    margin-top: 40px;
                    text-align: center;
                    font-size: 11px;
                    color: #999;
                    border-top: 2px solid #ddd;
                    padding-top: 20px;
                }
                .receipt-number-box {
                    background-color: #fff3cd;
                    border: 2px dashed #856404;
                    padding: 10px;
                    text-align: center;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .receipt-number-box strong {
                    font-size: 16px;
                    color: #856404;
                }
                .thank-you {
                    text-align: center;
                    margin: 30px 0;
                    font-size: 18px;
                    color: #16a34a;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="receipt-header">
                <h1>Payment Receipt</h1>
                <h2>' . htmlspecialchars($organization_name) . '</h2>
                <p>This is to acknowledge receipt of your payment</p>
            </div>
            
            <div class="receipt-number-box">
                <strong>Receipt Number: ' . htmlspecialchars($receipt_number) . '</strong><br>
                <span style="font-size: 12px; color: #666;">Date: ' . htmlspecialchars($receipt_date) . '</span>
            </div>
            
            <div class="receipt-info">
                <div class="receipt-info-left" style="padding-right: 10px;">
                    <div class="info-box">
                        <h3>Borrower Information</h3>
                        <div class="info-row">
                            <div class="info-label">Name:</div>
                            <div class="info-value">' . htmlspecialchars($borrower_name) . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone:</div>
                            <div class="info-value">' . htmlspecialchars($borrower_phone) . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value">' . htmlspecialchars($borrower_email) . '</div>
                        </div>
                    </div>
                </div>
                
                <div class="receipt-info-right" style="padding-left: 10px;">
                    <div class="info-box">
                        <h3>Loan Information</h3>
                        <div class="info-row">
                            <div class="info-label">Loan Number:</div>
                            <div class="info-value">' . htmlspecialchars($loan_number) . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Principal Amount:</div>
                            <div class="info-value">' . htmlspecialchars($formatted_principal) . '</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Loan Status:</div>
                            <div class="info-value">' . htmlspecialchars(ucfirst($loan->loan_status)) . '</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="payment-summary">
                <h2>Payment Received</h2>
                <div class="payment-amount">' . htmlspecialchars($formatted_payment) . '</div>
                <p style="margin: 5px 0; font-size: 14px;">Payment Method: ' . htmlspecialchars($payment_method) . '</p>
                <p style="margin: 5px 0; font-size: 12px;">Reference: ' . htmlspecialchars($reference_number) . '</p>
            </div>
            
            <div class="balance-section">
                <table>
                    <tr>
                        <td>Outstanding Balance After This Payment:</td>
                        <td style="color: ' . ($balance_after > 0 ? '#dc2626' : '#16a34a') . ';">' . htmlspecialchars($formatted_balance) . '</td>
                    </tr>
                </table>
            </div>
            
            <div class="thank-you">
                ✓ Thank You for Your Payment!
            </div>
            
            <div class="footer">
                <p><strong>Note:</strong> This is a computer-generated receipt and does not require a signature.</p>
                <p>Please keep this receipt for your records.</p>
                <p>&copy; ' . now()->year . ' ' . htmlspecialchars($organization_name) . '. All rights reserved.</p>
                <p style="margin-top: 10px; font-size: 10px;">If you have any questions regarding this payment, please contact us immediately.</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}
