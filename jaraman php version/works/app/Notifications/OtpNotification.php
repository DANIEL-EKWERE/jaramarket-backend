<?php

namespace App\Notifications;


use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class OtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public $otp)
    { }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $subject = "Your OTP"; 
        
        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.user_otp', [
                'user'  => $notifiable,
                'otp'  => $this->otp
            ]);
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "Your OTP is {$this->otp}",
            'otp'     => $this->otp,
            'user_id' => $notifiable->id,
            'email'   => $notifiable->email,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => "Your OTP is {$this->otp}",
            'otp'     => $this->otp,
            'user_id' => $notifiable->id,
            'email'   => $notifiable->email,
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'message' => "Your OTP is {$this->otp}",
            'otp'     => $this->otp,
            'user_id' => $notifiable->id,
            'email'   => $notifiable->email,
        ];
    }
}