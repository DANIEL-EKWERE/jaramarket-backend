<?php

namespace App\Channels;

use App\Services\Firebase\FirebaseNotificationService;
use Illuminate\Notifications\Notification;

/**
 * FirebaseChannel — plugs into Laravel notifications.
 *
 * Usage in any Notification:
 *   public function via($notifiable): array { return ['firebase', 'database']; }
 *   public function toFirebase($notifiable): array {
 *       return ['title' => 'Hello', 'body' => 'World', 'data' => ['key' => 'value']];
 *   }
 */
class FirebaseChannel
{
    public function __construct(protected FirebaseNotificationService $firebase) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toFirebase')) return;

        $payload = $notification->toFirebase($notifiable);

        $this->firebase->sendToUser(
            $notifiable,
            $payload['title'] ?? '',
            $payload['body']  ?? '',
            $payload['data']  ?? [],
        );
    }
}
