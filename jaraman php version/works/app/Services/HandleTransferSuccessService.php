<?php

namespace App\Services;

use App\Enums\WalletTransactionTypeEnum;
use App\Notifications\WalletNotification;
use App\Models\Transfer;
use App\Enums\StatusEnum;


class HandleTransferSuccessService
{
    public function handleWebhook($data)
    {
        $transfer_details = $data['data'];
    
        $transfer = Transfer::where('reference', $transfer_details['transfer_code'])->firstOrFail();
        
        $transfer->update(['status' => StatusEnum::SUCCESS()]);

        if (isset($transfer->owner)) {
                $user = $transfer->owner;
                $user->notify(new WalletNotification(
                WalletTransactionTypeEnum::DEBIT(),
                $transfer_details['amount'],
                $user->wallet->balance,
                $transfer_details['reference'],
                "Transfer successful"
            ));
        }
    }
}
