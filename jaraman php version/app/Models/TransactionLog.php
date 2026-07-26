<?php

namespace App\Models;

use App\Traits\AddPipelineToModelTrait;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Account\AccountTransactionTypeEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Enums\Account\AccountTransactionStatusEnum;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class TransactionLog extends Model
{
    use AddPipelineToModelTrait, HasFactory;

    const SMALLEST_CURRENCY_UNIT = 100;

    protected $fillable = [
        'account_owner_id',
        'account_owner_type',
        'owner_id',
        'owner_type',
        'reference',
        'amount',
        'transaction_type',
        'old_balance',
        'new_balance',
        'status',
        'comment',
        'is_refund',
        'has_refund',
        'wallet_id',
        'currency'
    ];

    public static function booted(): void
    {
        // Send account credit notification when a pending credit transaction is completed.
        static::updated(function (TransactionLog $account) {
            if (
                $account->wasChanged('status') &&
                $account->transaction_type === AccountTransactionTypeEnum::credit() &&
                $account->refresh()->status === AccountTransactionStatusEnum::completed()
            ) {
                AccountService::normalizePendingAccountTransactions($account);
            }
        });
    }

    public function account_owner()
    {
        return $this->morphTo();
    }

    public function owner()
    {
        return $this->morphTo();
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value / self::SMALLEST_CURRENCY_UNIT,
        );
    }

    
    public function beneficiaryName(): Attribute
    {
        return Attribute::make(
            function ($value) {

                return match ($this->owner_type) {
                    Order::class => $this->owner?->owner?->name,
                    default => $this->owner?->name,
                };

            }
        );
    }


    public function scopeInfo($query, int $user_id, ?string $virtual_account_reference = null)
    {
        // Using coalesce to prevent returning null when no sum is made, and ROUND to convert to 2 d.p
        $results = $query->where('account_owner_id', $user_id)
            ->where('account_owner_type', User::class)
            ->when($virtual_account_reference !== null, fn () => $query->whereRelation('wallet', 'reference', $virtual_account_reference))
            ->selectRaw('COALESCE(ROUND(SUM(CASE WHEN transaction_type = ? AND is_refund = false THEN amount ELSE 0 END) / '.self::SMALLEST_CURRENCY_UNIT.', 2), 0) AS total_credit', [AccountTransactionTypeEnum::credit()])
            ->selectRaw('COALESCE(ROUND(SUM(CASE WHEN transaction_type = ? THEN amount ELSE 0 END) / '.self::SMALLEST_CURRENCY_UNIT.', 2), 0) AS total_debit', [AccountTransactionTypeEnum::debit()])
            ->selectRaw('COALESCE(ROUND(SUM(CASE WHEN transaction_type = ? AND is_refund = true THEN amount ELSE 0 END) / '.self::SMALLEST_CURRENCY_UNIT.', 2), 0) AS total_refund', [AccountTransactionTypeEnum::credit()])
            ->where('status', AccountTransactionStatusEnum::completed())
            ->first()
            ->toArray();

        $results['total_credit'] = (float) $results['total_credit'];
        $results['total_debit'] = (float) ($results['total_debit'] - $results['total_refund']);
        $results['total_refund'] = (float) $results['total_refund'];

        return $results;
    }


    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function amountPaid()
    {
        return $this->amount - ($this->discount ?? 0);
    }
}
