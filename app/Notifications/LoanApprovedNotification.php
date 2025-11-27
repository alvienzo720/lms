<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $loan;
    protected $agreementFilePath;
    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Loan $loan, string $message, ?string $agreementFilePath = null)
    {
        $this->loan = $loan;
        $this->message = $message;
        $this->agreementFilePath = $agreementFilePath;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $borrowerName = $notifiable->first_name ?? 'Valued Customer';
        
        $mailMessage = (new MailMessage)
            ->subject('🎉 Loan Approved - ' . ($this->loan->loan_number ?? 'Your Loan'))
            ->greeting('Congratulations, ' . $borrowerName . '!')
            ->line($this->message)
            ->line('**Loan Details:**')
            ->line('• Loan Number: ' . ($this->loan->loan_number ?? 'N/A'))
            ->line('• Principal Amount: ' . number_format($this->loan->principal_amount ?? 0, 2))
            ->line('• Repayment Amount: ' . number_format($this->loan->repayment_amount ?? 0, 2))
            ->line('• Loan Duration: ' . ($this->loan->loan_duration ?? 'N/A') . ' ' . ($this->loan->loan_type->interest_cycle ?? ''))
            ->line('• Release Date: ' . ($this->loan->loan_release_date ?? 'N/A'))
            ->line('Please review the attached loan agreement document.')
            ->line('Thank you for choosing our services!');

        // Attach the loan agreement file if it exists
        if ($this->agreementFilePath && file_exists(public_path($this->agreementFilePath))) {
            $mailMessage->attach(public_path($this->agreementFilePath), [
                'as' => 'Loan_Agreement_' . ($this->loan->loan_number ?? 'document') . '.docx',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        }

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'loan_id' => $this->loan->id,
            'loan_number' => $this->loan->loan_number,
            'message' => $this->message,
        ];
    }
}
