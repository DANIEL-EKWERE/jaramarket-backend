<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PinNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $type;
    protected $token;
    protected $expiry;

    public function __construct(string $type, ?string $token = null, $expiry = null)
    {
        $this->type   = $type;
        $this->token  = $token;
        $this->expiry = $expiry;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $subject = match ($this->type) {
            'setup'           => "Transaction PIN Setup Successful",
            'updated'         => "Transaction PIN Updated",
            'reset_request'   => "Reset Your Transaction PIN",
            'reset_confirmed' => "Transaction PIN Reset Successful",
            'token_validated' => 'Your PIN token was successfully validated.',
            'token_invalid'   => 'There was an invalid attempt to use your PIN token.',
            'token_cleared'   => 'Your PIN token has been cleared from the system.',
            default           => "Transaction PIN Notification",
        };

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.pin', [
                'user'   => $notifiable,
                'type'   => $this->type,
                'token'  => $this->token,
                'expiry' => $this->expiry,
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'type'    => $this->type,
            'title'   => match($this->type) {
                'setup'           => 'PIN Setup',
                'updated'         => 'PIN Updated',
                'reset_request'   => 'PIN Reset Request',
                'reset_confirmed' => 'PIN Reset Successful',
                'token_validated' => 'Your PIN token was successfully validated.',
                'token_invalid'   => 'There was an invalid attempt to use your PIN token.',
                'token_cleared'   => 'Your PIN token has been cleared from the system.',
                default           => 'PIN Notification',
            },
            'message' => match($this->type) {
                'setup'           => 'Your transaction PIN has been set up successfully.',
                'updated'         => 'Your transaction PIN was successfully updated.',
                'reset_request'   => "PIN reset token: {$this->token} (expires in {$this->expiry} mins).",
                'reset_confirmed' => 'Your transaction PIN has been reset successfully.',
                'token_validated' => 'Your PIN token was successfully validated.',
                'token_invalid'   => 'There was an invalid attempt to use your PIN token.',
                'token_cleared'   => 'Your PIN token has been cleared from the system.',
                default           => 'A transaction PIN event occurred.',
            },
        ];
    }
}
