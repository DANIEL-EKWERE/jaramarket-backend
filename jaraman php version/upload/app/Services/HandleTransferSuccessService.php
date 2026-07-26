<?php

namespace App\Services;

use App\Enums\StatusEnum;
use App\Enums\WalletTransactionTypeEnum;
use App\Models\Transfer;
use App\Notifications\WalletNotification;

class HandleTransferSuccessService
{
    public function handleWebhook(array $payload): void
    {
        $data = $payload['data'];

        $transfer = Transfer::where('reference', $data['transfer_code'])
            ->whereNot('status', StatusEnum::SUCCESS())
            ->firstOrFail();

        $transfer->update(['status' => StatusEnum::SUCCESS()]);

        $user = $transfer->owner;
        if (! $user) return;

        $amount     = (float) ($data['amount'] / 100); // kobo → naira
        $newBalance = (float) ($user->wallet?->balance ?? 0);

        $user->notify(new WalletNotification(
            type      : WalletTransactionTypeEnum::DEBIT(),
            amount    : $amount,
            balance   : $newBalance,
            reference : $data['transfer_code'],
            remarks   : 'Bank transfer confirmed — ' . ($transfer->bank_name ?? ''),
        ));
    }
}
