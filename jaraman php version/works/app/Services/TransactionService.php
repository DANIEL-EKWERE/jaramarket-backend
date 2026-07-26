<?php

namespace App\Services;

use App\Utils\Util;
use App\Models\User;
use App\Models\PaymentLog;
use App\Enums\TransactionStatusEnum;
use App\Exceptions\GeneralException;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\UserRepositoryInterface;
use App\Enums\WalletTransactionTypeEnum;
use App\Notifications\WalletNotification;

class TransactionService
{
    public function __construct(
        public PaymentGatewayInterface $gateway,
        public UserRepositoryInterface $userRepository
    ) {}

    public function updateTransaction(array $data, ?User $transaction_owner = null, ?string $transaction_owner_type = null)
    {
        if ($transaction = PaymentLog::firstWhere('txn_ref', $data['reference'])) {
            $amount = Util::get_amount_by_currency($data['amount'], $data['currency']);

            //$vat = Util::VAT($total);
            //$amount = $total - $vat;

            $this->updateWallet($amount, $transaction_owner);

            $transaction_owner->notify(new WalletNotification(
                WalletTransactionTypeEnum::CREDIT(),
                number_format($amount,2),
                number_format($transaction_owner->wallet->balance,2),
                $data['reference'],
                "You have successfully fund your wallet"
            ));
               
            return tap($transaction)->update([
                'status' => $data['status'],
                'amount' => $amount,
                'gateway_response' => $data['gateway_response'],
                'transaction_mode' => $data['channel'],
                'transaction_owner_id' => $transaction_owner->id,
                'transaction_owner_type' => $transaction_owner_type,
            ]);
        }

        return false;
    }

    public function checkDuplicateTransaction($reference)
    {
        if (PaymentLog::where('txn_ref', $reference)->where('status', TransactionStatusEnum::PAYMENT_SUCCESSFUL())->exists()) {
            throw new GeneralException('Duplicate transaction', 400);
        }
    }

    public function updateWallet($amount, $user)
    {
        $user->wallet->increment('balance', $amount);
    }
}
