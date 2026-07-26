<?php

namespace App\Notifications;

use App\Enums\UserPermissionsEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class OrderCancelledNotification extends Notification implements ShouldQueue
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
            ->subject('Order Cancelled')
            ->markdown('emails.order_cancelled', [
                'user'    => $notifiable,
                'order'   => $this->order,
                'message' => $this->getMessage($notifiable),
            ]);
    }

    public function toFirebase($notifiable): array
    {
        return [
            'title' => 'Order Cancelled',
            'body'  => $this->getMessage($notifiable),
            'data'  => [
                'order_id' => $this->order->id,
                'status'   => $this->order->status,
                'total'    => $this->order->total,
                'type'     => 'order.cancelled',
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
            'message'  => $this->getMessage($notifiable),
            'order_id' => $this->order->id,
            'status'   => $this->order->status,
            'total'    => $this->order->total,
            'user_id'  => $notifiable->id,
        ];
    }

    private function getMessage($notifiable): string
    {
        if ($notifiable->role === UserPermissionsEnum::VENDOR()) {
            return "Order #{$this->order->reference} from your store has been cancelled.";
        }

        if ($notifiable->role === UserPermissionsEnum::ADMIN()) {
            return "Order #{$this->order->reference} has been cancelled.";
        }

        return "Your order #{$this->order->reference} has been cancelled.";
    }
}
