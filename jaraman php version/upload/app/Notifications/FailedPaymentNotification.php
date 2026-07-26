<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class FailedPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public $email, public array $data) {}

    public function via($notifiable): array
    {
        return ['mail', 'database', 'firebase'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Unknown Payment Detected')
            ->markdown('emails.failed_payment', [
                'user'  => $notifiable,
                'email' => $this->email,
                'data'  => $this->data,
            ]);
    }

    public function toFirebase($notifiable): array
    {
        return [
            'title' => 'Payment Alert',
            'body'  => "A payment attempt from {$this->email} could not be verified.",
            'data'  => [
                'email' => $this->email,
                'type'  => 'payment.failed',
            ],
        ];
    }

    public function toDatabase($notifiable): array
    {
        return $this->payload($notifiable);
    }

    public function toArray($notifiable): array
    {
        return $this->payload($notifiable);
    }

    private function payload($notifiable): array
    {
        return [
            'message' => "A payment attempt from {$this->email} could not be verified.",
            'email'   => $this->email,
            'data'    => $this->data,
            'user_id' => $notifiable->id,
        ];
    }
}
