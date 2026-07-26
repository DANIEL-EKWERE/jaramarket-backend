<?php

namespace App\Services;

use App\Notifications\WalletNotification;
use Carbon\Carbon;
use App\Utils\Util;
use App\Models\User;
use App\Models\Wallet;
use App\Models\PaymentLog;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\DB;
use App\Enums\TransactionStatusEnum;
use App\Exceptions\GeneralException;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\UserRepositoryInterface;
use App\Enums\Account\AccountTransactionTypeEnum;

class TransactionLogService
{
    protected function get_account_owner(string $account_owner_type, int $account_owner_id, ?int $wallet_id = null)
    {
        return TransactionLog::with('owner')->whereHasMorph('account_owner', [$account_owner_type], function ($query) use ($account_owner_id) {
            $query->where('id', $account_owner_id);
        })->when(! is_null($wallet_id), fn ($query) => $query->where('wallet_id', $wallet_id));
    }

    public static function get_wallet_account(string $account_owner_id, ?string $reference = null)
    {
        if ($reference) {
            return Wallet::where('id', $reference)->where('user_id', $account_owner_id)->first();
        }

        return User::with('wallet')->find($account_owner_id)->first();
    }

    public static function get_old_balance(int $account_owner_id, string $account_owner_type, ?int $wallet_id = null)
    {
        return (new TransactionLogService())->get_account_owner($account_owner_type, $account_owner_id, $wallet_id)?->latest('id')?->first()?->new_balance ?? 0;
    }

    public static function debit(
        int $account_owner_id,
        string $account_owner_type,
        $amount,
        ?int $owner_id = null,
        ?string $owner_type = null,
        string $currency = 'NGN',
        ?string $comment = null
    ) {

        $wallet = Wallet::where('user_id', $account_owner_id)->first();
        $old_balance = $wallet->balance;//self::get_old_balance($account_owner_id, $account_owner_type, $wallet?->id);
        $new_balance = $old_balance - $amount;

        DB::beginTransaction();

        $wallet?->update(['balance' => $new_balance]);
        
        $account = TransactionLog::create([
            'account_owner_id' => $account_owner_id,
            'account_owner_type' => $account_owner_type,
            'owner_id' => $owner_id,
            'owner_type' => $owner_type,
            'amount' => $amount,
            'transaction_type' => AccountTransactionTypeEnum::debit(),
            'reference' => Util::generate_order_txn_ref(),
            'old_balance' => $old_balance,
            'new_balance' => $new_balance,
            'wallet_id' => $wallet?->id,
            'currency'  => $currency,
            'comment' => $comment,
            'is_refund' => false,
            'has_refund' => false
        ]);

        DB::commit();
        return $account;
    }
    
    public static function credit(
        int $account_owner_id,
        string $account_owner_type,
        $amount,
        ?int $owner_id = null,
        ?string $owner_type = null,
        string $currency = 'NGN',
        ?string $comment = null
    ) {
        $wallet = Wallet::where('user_id', $account_owner_id)->first();
        $old_balance = $wallet->balance ?? 0;
        $new_balance = $old_balance + $amount;
    
        DB::beginTransaction();
    
        $wallet?->update(['balance' => $new_balance]);
    
        $account = TransactionLog::create([
            'account_owner_id' => $account_owner_id,
            'account_owner_type' => $account_owner_type,
            'owner_id' => $owner_id,
            'owner_type' => $owner_type,
            'amount' => $amount,
            'transaction_type' => AccountTransactionTypeEnum::credit(),
            'reference' => Util::generate_order_txn_ref(),
            'old_balance' => $old_balance,
            'new_balance' => $new_balance,
            'wallet_id' => $wallet?->id,
            'currency'  => $currency,
            'comment' => $comment,
            'is_refund' => true,
            'has_refund' => false,
        ]);
    
        DB::commit();
        return $account;
    }
    

    public function get_transactions_by_date_range(string $user_type, int $user_id, ?string $start = null, ?string $end = null)
    {
        return $this->get_account_owner($user_type, $user_id)
            ->when($start, function ($query) use ($start) {
                $query->whereDate('created_at', '>=', Carbon::parse($start)->startOfDay());
            })
            ->when($end, function ($query) use ($end) {
                $query->whereDate('created_at', '<=', Carbon::parse($end)->endOfDay());
            })
            ->get();
    }
}