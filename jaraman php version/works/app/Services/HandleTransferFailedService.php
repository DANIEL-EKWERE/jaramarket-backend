<?php

namespace App\Services;

use App\Models\User;
use App\Services\TransactionLogService;
use App\Enums\WalletTransactionTypeEnum;
use App\Notifications\WalletNotification;
use App\Models\Transfer;
use App\Enums\StatusEnum;


class HandleTransferFailedService
{
    public function __construct(
        public TransactionLogService $transactionLogService
    ) {}

    public function handleWebhook($data)
    {
        $transfer_details = $data['data'];

        $transfer = Transfer::where('reference', $transfer_details['transfer_code'])->whereNot('status', StatusEnum::SUCCESS())->firstOrFail();
        
        $transfer->update(['status' => StatusEnum::FAILED()]);

        $this->transactionLogService::credit($transfer->owner->id,User::class,$transfer_details['amount'],$transfer->id,get_class($transfer));
        
        if (isset($transfer->owner)) {
                $user = $transfer->owner;
                $user->notify(new WalletNotification(
                WalletTransactionTypeEnum::CREDIT(),
                $transfer_details['amount'],
                $user->wallet->balance,
                $transfer_details['reference'],
                "Reversed from transfer"
            ));
        }
    }
}
