<?php

namespace App\Notifications;

use App\Enums\WalletTransactionTypeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WalletNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public float $amount;
    public float $balance;

    public function __construct(
        public string  $type,
        float|int      $amount,
        float|int      $balance,
        public ?string $reference = null,
        public ?string $remarks   = null,
    ) {
        $this->amount  = (float) $amount;
        $this->balance = (float) $balance;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database', 'firebase'];
    }

    public function toMail($notifiable): MailMessage
    {
        $isCredit = $this->type === WalletTransactionTypeEnum::CREDIT();
        $subject  = $isCredit
            ? 'Wallet Credited — ₦' . number_format($this->amount, 2)
            : 'Wallet Debited — ₦' . number_format($this->amount, 2);

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.wallet', [
                'user'      => $notifiable,
                'type'      => $this->type,
                'amount'    => $this->amount,
                'balance'   => $this->balance,
                'reference' => $this->reference,
                'remarks'   => $this->remarks,
            ]);
    }

    public function toDatabase($notifiable): array { return $this->payload($notifiable); }
    public function toArray($notifiable): array    { return $this->payload($notifiable); }

    public function toFirebase($notifiable): array
    {
        $isCredit = $this->type === WalletTransactionTypeEnum::CREDIT();

        return [
            'title' => $isCredit ? 'Wallet Credited' : 'Wallet Debited',
            'body'  => '₦' . number_format($this->amount, 2) . ' '
                     . ($isCredit ? 'added to' : 'deducted from') . ' your wallet.',
            'data'  => [
                'type'      => $isCredit ? 'wallet_credit' : 'wallet_debit',
                'amount'    => (string) $this->amount,
                'balance'   => (string) $this->balance,
                'reference' => (string) ($this->reference ?? ''),
                'remarks'   => (string) ($this->remarks   ?? ''),
            ],
        ];
    }

    private function payload($notifiable): array
    {
        $isCredit = $this->type === WalletTransactionTypeEnum::CREDIT();
        return [
            'message'   => '₦' . number_format($this->amount, 2) . ' was ' . ($isCredit ? 'credited to' : 'debited from') . ' your wallet.',
            'type'      => $this->type,
            'amount'    => $this->amount,
            'balance'   => $this->balance,
            'reference' => $this->reference,
            'remarks'   => $this->remarks,
            'user_id'   => $notifiable->id,
        ];
    }
}
