<?php

namespace App\Notifications;

use App\Enums\OrderNotificationTypeEnum;
use App\Enums\StatusEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $order,
        public $recipientType,
        public $status
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database', 'firebase'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = match ($this->status) {
            StatusEnum::PROCESSING() => "Order #{$this->order->reference} is Processing",
            StatusEnum::COMPLETED()  => "Order #{$this->order->reference} is Ready",
            default                  => "Order #{$this->order->reference} Update",
        };
        return (new MailMessage)->subject($subject)
            ->markdown('emails.order_status', [
                'user'          => $notifiable,
                'order'         => $this->order,
                'recipientType' => $this->recipientType,
                'status'        => $this->status,
            ]);
    }

    public function toDatabase($notifiable): array { return $this->formatMessage($notifiable); }
    public function toArray($notifiable): array    { return $this->formatMessage($notifiable); }

    public function toFirebase($notifiable): array
    {
        $msg = $this->formatMessage($notifiable);
        return [
            'title' => match ($this->status) {
                StatusEnum::PROCESSING() => "Order #{$this->order->reference} Processing",
                StatusEnum::COMPLETED()  => "Order #{$this->order->reference} Completed",
                default                  => "Order #{$this->order->reference} Update",
            },
            'body'  => $msg['message'],
            'data'  => ['order_id' => (string) $this->order->id, 'status' => $this->status, 'type' => 'order_status'],
        ];
    }

    protected function formatMessage($notifiable): array
    {
        $message = match ($this->recipientType) {
            OrderNotificationTypeEnum::CUSTOMER() => match ($this->status) {
                StatusEnum::PROCESSING() => "Your order #{$this->order->reference} is now processing.",
                StatusEnum::COMPLETED()  => "Your order #{$this->order->reference} is ready.",
                default => "Update for your order #{$this->order->reference}.",
            },
            OrderNotificationTypeEnum::VENDOR() => "You accepted items from order #{$this->order->reference}.",
            OrderNotificationTypeEnum::ADMIN()  => "Order #{$this->order->reference} status: {$this->status}.",
            default => "Order #{$this->order->reference} update.",
        };
        return [
            'message'   => $message,
            'order_id'  => $this->order->id,
            'status'    => $this->status,
            'total'     => $this->order->total,
            'user_id'   => $notifiable->id,
            'recipient' => $this->recipientType,
        ];
    }
}
