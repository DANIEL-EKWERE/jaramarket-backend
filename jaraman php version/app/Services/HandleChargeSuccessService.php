<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\PaymentLog;
use App\Models\User;
use App\Notifications\FailedPaymentNotification;
use App\Utils\Util;

class HandleChargeSuccessService
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    public function handleWebhook(array $payload): void
    {
        $data  = $payload['data'];
        $email = Util::isEmailModified($data['customer']['email'])
            ? Util::getOriginalEmail($data['customer']['email'])
            : $data['customer']['email'];

        $user  = User::where('email', $email)->first();
        $admin = Admin::first();

        if (! $user) {
            $admin?->notify(new FailedPaymentNotification($email, $data));
            return;
        }

        // Idempotency — skip if already processed
        if (PaymentLog::where('txn_ref', $data['reference'])->where('status', 'success')->exists()) {
            return;
        }

        $amount = Util::get_amount_by_currency($data['amount'], $data['currency']);

        // Mark payment log as successful
        PaymentLog::where('txn_ref', $data['reference'])->update([
            'status'                 => $data['status'],
            'amount'                 => $amount,
            'gateway_response'       => $data['gateway_response'] ?? null,
            'transaction_mode'       => $data['channel'] ?? null,
            'transaction_owner_id'   => $user->id,
            'transaction_owner_type' => get_class($user),
        ]);

        // Credit wallet and send notifications
        $this->walletService->credit(
            user      : $user,
            amount    : $amount,
            reference : $data['reference'],
            comment   : 'Wallet funding via ' . ucfirst($data['channel'] ?? 'paystack'),
        );
    }
}
