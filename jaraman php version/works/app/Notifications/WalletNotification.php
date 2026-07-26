<?php

namespace App\Notifications;

use App\Enums\WalletTransactionTypeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

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
        public ?string $remarks   = null
    ) {
        // Always store as float — prevents string-type errors from callers
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
            ? 'Wallet Credited: ₦' . number_format($this->amount, 2)
            : 'Wallet Debited: ₦'  . number_format($this->amount, 2);

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

    public function toDatabase($notifiable): array { return $this->formatPayload($notifiable); }
    public function toArray($notifiable): array    { return $this->formatPayload($notifiable); }

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
            ],
        ];
    }

    protected function formatPayload($notifiable): array
    {
        $isCredit = $this->type === WalletTransactionTypeEnum::CREDIT();
        $message  = '₦' . number_format($this->amount, 2) . ' was '
            . ($isCredit ? 'credited to' : 'debited from') . ' your wallet.';

        return [
            'message'   => $message,
            'type'      => $this->type,
            'amount'    => $this->amount,
            'balance'   => $this->balance,
            'reference' => $this->reference,
            'remarks'   => $this->remarks,
            'user_id'   => $notifiable->id,
        ];
    }
}
