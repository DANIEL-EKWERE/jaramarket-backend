<?php

namespace App\Services;

use App\Services\Firebase\FirebaseNotificationService;
use App\Services\PaymentGateways\Paystack;
use App\Enums\WalletTransactionTypeEnum;
use App\Models\User;
use App\Models\TransactionLog;
use App\Notifications\WalletNotification;
use Exception;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        protected TransactionLogService $transactionLogService,
        protected Paystack $paystack,
        protected FirebaseNotificationService $firebase
    ) {}

    public function transferToBank(User $user, array $data)
    {
        if ($user->wallet->balance < $data['amount']) {
            throw new Exception('Insufficient balance.');
        }

        return DB::transaction(function () use ($user, $data) {
            $bankAccount = $user->bankAccounts()->where('id', $data['bank_id'])->firstOrFail();

            $transfer = $this->paystack->payout(
                bank_account: $bankAccount,
                amount: $data['amount'],
                owner_id: $user->id,
                owner_type: get_class($user)
            );

            $this->transactionLogService::debit(
                $user->id, get_class($user), $data['amount'],
                null, null, $data['currency'] ?? 'NGN', $data['remark'] ?? 'Bank withdrawal'
            );

            $amount     = (float) $data['amount'];
            $newBalance = (float) $user->wallet->fresh()->balance;

            $this->firebase->sendToUser(
                $user,
                'Withdrawal Initiated',
                '₦' . number_format($amount, 2) . ' withdrawal has been initiated.',
                [
                    'type'      => 'withdrawal_initiated',
                    'amount'    => (string) $amount,
                    'reference' => $transfer->reference ?? '',
                ]
            );

            // Pass raw floats — WalletNotification formats them internally
            $user->notify(new WalletNotification(
                WalletTransactionTypeEnum::DEBIT(),
                $amount,
                $newBalance,
                $transfer->reference ?? null,
                $data['remark'] ?? 'Bank withdrawal'
            ));

            return $transfer;
        });
    }

    public function getTransactionHistory(User $user, int $perPage = 20, ?string $type = null)
    {
        return TransactionLog::where('account_owner_id', $user->id)
            ->where('account_owner_type', get_class($user))
            ->when($type, fn ($q) => $q->where('transaction_type', $type))
            ->with('owner')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
