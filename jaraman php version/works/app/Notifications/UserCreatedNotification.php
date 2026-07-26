<?php

namespace App\Notifications;


use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UserCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
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
        $subject = "Your Account Created Successfully"; 
        
        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.user_registered', [
                'user'  => $notifiable
            ]);
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "Your Account {$notifiable->name} was created successfully.",
            'user_id' => $notifiable->id,
            'email'   => $notifiable->email,
        ];
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
            'message' => "Your account {$notifiable->name} was created successfully.",
            'user_id' => $notifiable->id,
            'email'   => $notifiable->email,
        ];
    }
}