<?php

namespace App\Services\Firebase;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Contract\Messaging;

class FirebaseNotificationService
{
    public function __construct(protected Messaging $messaging) {}

    /**
     * Send a push notification to a single user via their stored FCM token.
     *
     * @param  User   $user   The recipient — must have a non-null fcm_token column.
     * @param  string $title  Notification title shown on the device.
     * @param  string $body   Notification body text.
     * @param  array  $data   Arbitrary key/value payload delivered alongside the notification.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        if (empty($user->fcm_token)) {
            return;
        }

        $this->sendToToken($user->fcm_token, $title, $body, $data);
    }

    /**
     * Send to a raw FCM registration token (useful when you have the token
     * but not a User model, e.g. guest sessions or vendor devices).
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($this->stringifyData($data));

            $this->messaging->send($message);
        } catch (\Throwable $e) {
            // Never let a push notification failure bubble up and break the
            // business transaction — log and continue.
            Log::error('[Firebase] Failed to send notification', [
                'token'   => substr($token, 0, 20) . '…',
                'title'   => $title,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fan-out to multiple users at once (e.g. all admins).
     *
     * @param  iterable<User> $users
     */
    public function sendToUsers(iterable $users, string $title, string $body, array $data = []): void
    {
        foreach ($users as $user) {
            $this->sendToUser($user, $title, $body, $data);
        }
    }

    /**
     * FCM data payloads must be string => string maps.
     * Cast everything so callers can pass mixed values freely.
     */
    private function stringifyData(array $data): array
    {
        return array_map(
            fn ($v) => is_array($v) || is_object($v) ? json_encode($v) : (string) $v,
            $data
        );
    }
}
