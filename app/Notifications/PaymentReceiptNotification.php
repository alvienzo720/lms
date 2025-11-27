<?php

namespace App\Notifications;

use App\Models\Repayments;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $repayment;
    protected $loan;
    protected $message;
    protected $receiptFilePath;

    /**
     * Create a new notification instance.
     */
    public function __construct(Repayments $repayment, Loan $loan, string $message, ?string $receiptFilePath = null)
    {
        $this->repayment = $repayment;
        $this->loan = $loan;
        $this->message = $message;
        $this->receiptFilePath = $receiptFilePath;
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
        $paymentAmount = number_format($this->repayment->payments ?? 0, 2);
        $balance = number_format($this->repayment->balance ?? 0, 2);
        
        $mailMessage = (new MailMessage)
            ->subject('✓ Payment Received - ' . ($this->loan->loan_number ?? 'Your Loan'))
            ->greeting('Hello, ' . $borrowerName . '!')
            ->line($this->message)
            ->line('**Payment Details:**')
            ->line('• Amount Paid: ' . $paymentAmount)
            ->line('• Payment Method: ' . ($this->repayment->payments_method ?? 'N/A'))
            ->line('• Reference Number: ' . ($this->repayment->reference_number ?? 'N/A'))
            ->line('• Remaining Balance: ' . $balance)
            ->line('• Loan Number: ' . ($this->loan->loan_number ?? 'N/A'));

        if ($this->repayment->balance <= 0) {
            $mailMessage->line('🎉 **Congratulations!** Your loan has been fully paid!');
        }

        $mailMessage->line('Your payment receipt is attached to this email for your records.')
            ->line('Thank you for your payment!');

        // Attach the receipt PDF if it exists
        if ($this->receiptFilePath && file_exists(public_path($this->receiptFilePath))) {
            $mailMessage->attach(public_path($this->receiptFilePath), [
                'as' => 'Payment_Receipt_' . ($this->repayment->id ?? 'document') . '.pdf',
                'mime' => 'application/pdf',
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
            'repayment_id' => $this->repayment->id,
            'loan_id' => $this->loan->id,
            'payment_amount' => $this->repayment->payments,
            'balance' => $this->repayment->balance,
            'message' => $this->message,
        ];
    }
}
