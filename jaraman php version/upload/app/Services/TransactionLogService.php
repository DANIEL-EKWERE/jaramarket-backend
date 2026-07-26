<?php

namespace App\Services;

use App\Enums\Account\AccountTransactionTypeEnum;
use App\Models\TransactionLog;
use App\Models\Wallet;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionLogService
{
    /*
    |--------------------------------------------------------------------------
    | DEBIT — deduct from wallet, write transaction log
    |--------------------------------------------------------------------------
    */
    public static function debit(
        int     $account_owner_id,
        string  $account_owner_type,
        float   $amount,
        ?int    $owner_id   = null,
        ?string $owner_type = null,
        string  $currency   = 'NGN',
        ?string $comment    = null,
    ): TransactionLog {
        $wallet      = Wallet::where('user_id', $account_owner_id)->lockForUpdate()->firstOrFail();
        $old_balance = (float) $wallet->balance;
        $new_balance = $old_balance - $amount;

        $wallet->update(['balance' => $new_balance]);

        return TransactionLog::create([
            'account_owner_id'   => $account_owner_id,
            'account_owner_type' => $account_owner_type,
            'owner_id'           => $owner_id,
            'owner_type'         => $owner_type,
            'amount'             => $amount * 100,
            'transaction_type'   => AccountTransactionTypeEnum::debit(),
            'reference'          => Util::generate_order_txn_ref(),
            'old_balance'        => $old_balance * 100,
            'new_balance'        => $new_balance * 100,
            'wallet_id'          => $wallet->id,
            'currency'           => $currency,
            'comment'            => $comment,
            'is_refund'          => false,
            'has_refund'         => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREDIT — add to wallet, write transaction log
    |--------------------------------------------------------------------------
    */
    public static function credit(
        int     $account_owner_id,
        string  $account_owner_type,
        float   $amount,
        ?int    $owner_id   = null,
        ?string $owner_type = null,
        string  $currency   = 'NGN',
        ?string $comment    = null,
        bool    $is_refund  = false,
    ): TransactionLog {
        $wallet      = Wallet::where('user_id', $account_owner_id)->lockForUpdate()->firstOrFail();
        $old_balance = (float) $wallet->balance;
        $new_balance = $old_balance + $amount;

        $wallet->update(['balance' => $new_balance]);

        return TransactionLog::create([
            'account_owner_id'   => $account_owner_id,
            'account_owner_type' => $account_owner_type,
            'owner_id'           => $owner_id,
            'owner_type'         => $owner_type,
            'amount'             => $amount * 100,
            'transaction_type'   => AccountTransactionTypeEnum::credit(),
            'reference'          => Util::generate_order_txn_ref(),
            'old_balance'        => $old_balance * 100,
            'new_balance'        => $new_balance * 100,
            'wallet_id'          => $wallet->id,
            'currency'           => $currency,
            'comment'            => $comment,
            'is_refund'          => $is_refund,
            'has_refund'         => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY HELPERS
    |--------------------------------------------------------------------------
    */
    protected function get_account_owner(string $account_owner_type, int $account_owner_id, ?int $wallet_id = null)
    {
        return TransactionLog::with('owner')
            ->whereHasMorph('account_owner', [$account_owner_type], function ($query) use ($account_owner_id) {
                $query->where('id', $account_owner_id);
            })
            ->when(! is_null($wallet_id), fn ($query) => $query->where('wallet_id', $wallet_id));
    }

    public function get_transactions_by_date_range(
        string  $user_type,
        int     $user_id,
        ?string $start = null,
        ?string $end   = null
    ) {
        return $this->get_account_owner($user_type, $user_id)
            ->when($start, fn ($q) => $q->whereDate('created_at', '>=', Carbon::parse($start)->startOfDay()))
            ->when($end,   fn ($q) => $q->whereDate('created_at', '<=', Carbon::parse($end)->endOfDay()))
            ->get();
    }

    public static function get_old_balance(int $account_owner_id, string $account_owner_type, ?int $wallet_id = null): float
    {
        $last = (new self)->get_account_owner($account_owner_type, $account_owner_id, $wallet_id)
            ->latest('id')
            ->first();

        return $last ? (float) ($last->new_balance / 100) : 0.0;
    }
}
