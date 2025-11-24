<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Services\RepaymentScheduleService;
use App\Services\RepaymentSchedulePDF;
use Illuminate\Http\Response;

class RepaymentScheduleController extends Controller
{
    protected RepaymentScheduleService $scheduleService;
    protected RepaymentSchedulePDF $pdfService;
    
    public function __construct(
        RepaymentScheduleService $scheduleService,
        RepaymentSchedulePDF $pdfService
    ) {
        $this->scheduleService = $scheduleService;
        $this->pdfService = $pdfService;
    }
    
    /**
     * Download repayment schedule as PDF
     *
     * @param Loan $loan
     * @return Response
     */
    public function downloadPdf(Loan $loan): Response
    {
        // The global scope on the Loan model will ensure
        // users can only access loans from their organization/branch
        
        return $this->pdfService->generate($loan);
    }
}
