<?php

namespace App\Services;

use App\Enums\WalletTransactionTypeEnum;
use App\Exceptions\GeneralException;
use App\Models\BankAccount;
use App\Models\TransactionLog;
use App\Models\User;
use App\Notifications\WalletNotification;
use App\Services\Firebase\FirebaseNotificationService;
use App\Services\PaymentGateways\Paystack;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        protected Paystack $paystack,
        protected FirebaseNotificationService $firebase
    ) {}

    /*
    |--------------------------------------------------------------------------
    | 1. FUND WALLET — initialize Paystack transaction, return payment URL
    |--------------------------------------------------------------------------
    */
    public function initializeFunding(User $user, array $data): string
    {
        $this->paystack
            ->setTransactionInitiator($user)
            ->setTransactionOwner($user);

        $result = $this->paystack->initializeTransaction(
            email    : $user->email,
            amount   : (float) $data['amount'],
            currency : $data['currency'] ?? 'NGN',
            callback : $data['callback_url'],
            metadata : array_merge(
                ['purpose' => 'wallet_funding', 'user_id' => $user->id],
                $data['metadata'] ?? []
            ),
        );

        return $result['authorization_url'];
    }

    /*
    |--------------------------------------------------------------------------
    | 2. CREDIT WALLET — called by HandleChargeSuccessService after webhook
    |--------------------------------------------------------------------------
    */
    public function credit(
        User    $user,
        float   $amount,
        string  $reference,
        string  $comment = 'Wallet funding'
    ): void {
        DB::transaction(function () use ($user, $amount, $reference, $comment) {
            TransactionLogService::credit(
                account_owner_id   : $user->id,
                account_owner_type : get_class($user),
                amount             : $amount,
                currency           : 'NGN',
                comment            : $comment,
            );

            $newBalance = (float) $user->wallet->fresh()->balance;

            $this->firebase->sendToUser(
                $user,
                'Wallet Funded',
                '₦' . number_format($amount, 2) . ' has been added to your wallet.',
                [
                    'type'      => 'wallet_credit',
                    'amount'    => (string) $amount,
                    'balance'   => (string) $newBalance,
                    'reference' => $reference,
                ]
            );

            $user->notify(new WalletNotification(
                type      : WalletTransactionTypeEnum::CREDIT(),
                amount    : $amount,
                balance   : $newBalance,
                reference : $reference,
                remarks   : $comment,
            ));
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 3. DEBIT WALLET — used when customer pays for an order
    |--------------------------------------------------------------------------
    */
    public function debit(
        User    $user,
        float   $amount,
        ?int    $ownerId   = null,
        ?string $ownerType = null,
        string  $comment   = 'Wallet debit'
    ): void {
        $this->ensureSufficientBalance($user, $amount);

        DB::transaction(function () use ($user, $amount, $ownerId, $ownerType, $comment) {
            TransactionLogService::debit(
                account_owner_id   : $user->id,
                account_owner_type : get_class($user),
                amount             : $amount,
                owner_id           : $ownerId,
                owner_type         : $ownerType,
                currency           : 'NGN',
                comment            : $comment,
            );

            $newBalance = (float) $user->wallet->fresh()->balance;

            $this->firebase->sendToUser(
                $user,
                'Wallet Debited',
                '₦' . number_format($amount, 2) . ' was deducted from your wallet.',
                [
                    'type'    => 'wallet_debit',
                    'amount'  => (string) $amount,
                    'balance' => (string) $newBalance,
                ]
            );

            $user->notify(new WalletNotification(
                type    : WalletTransactionTypeEnum::DEBIT(),
                amount  : $amount,
                balance : $newBalance,
                remarks : $comment,
            ));
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 4. TRANSFER TO BANK — Paystack payout to user's saved bank account
    |--------------------------------------------------------------------------
    */
    public function transferToBank(User $user, array $data): array
    {
        $amount = (float) $data['amount'];
        $this->ensureSufficientBalance($user, $amount);

        return DB::transaction(function () use ($user, $data, $amount) {
            /** @var BankAccount $bankAccount */
            $bankAccount = $user->bankAccounts()->findOrFail($data['bank_id']);

            // Debit wallet immediately (held pending Paystack transfer confirmation)
            TransactionLogService::debit(
                account_owner_id   : $user->id,
                account_owner_type : get_class($user),
                amount             : $amount,
                currency           : $data['currency'] ?? 'NGN',
                comment            : $data['remark'] ?? 'Bank withdrawal',
            );

            // Initiate Paystack payout
            $transfer  = $this->paystack->payout(
                bank_account : $bankAccount,
                amount       : (int) $amount,
                owner_id     : $user->id,
                owner_type   : get_class($user),
            );

            $newBalance = (float) $user->wallet->fresh()->balance;
            $reference  = $transfer->reference ?? null;

            $this->firebase->sendToUser(
                $user,
                'Withdrawal Initiated',
                '₦' . number_format($amount, 2) . ' withdrawal initiated to ' . $bankAccount->bank_name . '.',
                [
                    'type'      => 'withdrawal_initiated',
                    'amount'    => (string) $amount,
                    'reference' => (string) ($reference ?? ''),
                ]
            );

            $user->notify(new WalletNotification(
                type      : WalletTransactionTypeEnum::DEBIT(),
                amount    : $amount,
                balance   : $newBalance,
                reference : $reference,
                remarks   : $data['remark'] ?? 'Bank withdrawal to ' . $bankAccount->bank_name,
            ));

            return [
                'reference'      => $reference,
                'account_name'   => $bankAccount->account_name,
                'account_number' => $bankAccount->account_number,
                'bank_name'      => $bankAccount->bank_name,
                'amount'         => $amount,
                'new_balance'    => $newBalance,
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 5. TRANSACTION HISTORY
    |--------------------------------------------------------------------------
    */
    public function getTransactionHistory(User $user, int $perPage = 20, ?string $type = null): LengthAwarePaginator
    {
        return TransactionLog::where('account_owner_id', $user->id)
            ->where('account_owner_type', get_class($user))
            ->when($type, fn ($q) => $q->where('transaction_type', $type))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE HELPERS
    |--------------------------------------------------------------------------
    */
    private function ensureSufficientBalance(User $user, float $amount): void
    {
        $balance = (float) ($user->wallet?->balance ?? 0);

        if ($balance < $amount) {
            throw new GeneralException(
                'Insufficient wallet balance. Available: ₦' . number_format($balance, 2),
                400
            );
        }
    }
}
