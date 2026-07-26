<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class OrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public $order) {}

    public function via($notifiable): array
    {
        return ['mail', 'database', 'firebase'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order Confirmation')
            ->markdown('emails.order_confirmation', [
                'user'  => $notifiable,
                'order' => $this->order,
            ]);
    }

    public function toFirebase($notifiable): array
    {
        return [
            'title' => 'Order Placed',
            'body'  => "Your order #{$this->order->reference} has been placed successfully.",
            'data'  => [
                'order_id' => $this->order->id,
                'status'   => $this->order->status,
                'total'    => $this->order->total,
                'type'     => 'order.created',
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
            'message'  => "Your order #{$this->order->reference} has been placed successfully.",
            'order_id' => $this->order->id,
            'status'   => $this->order->status,
            'total'    => $this->order->total,
            'user_id'  => $notifiable->id,
        ];
    }
}
