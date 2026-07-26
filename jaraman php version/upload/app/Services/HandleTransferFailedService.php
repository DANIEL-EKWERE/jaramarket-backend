<?php

namespace App\Services;

use App\Enums\StatusEnum;
use App\Models\Transfer;

class HandleTransferFailedService
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    public function handleWebhook(array $payload): void
    {
        $data = $payload['data'];

        $transfer = Transfer::where('reference', $data['transfer_code'])
            ->whereNot('status', StatusEnum::SUCCESS())
            ->firstOrFail();

        // Idempotency — already reversed
        if ($transfer->status === StatusEnum::FAILED()) return;

        $transfer->update(['status' => StatusEnum::FAILED()]);

        $user = $transfer->owner;
        if (! $user) return;

        $amount = (float) ($data['amount'] / 100); // kobo → naira

        // Reverse the debit — credit wallet back with notification
        $this->walletService->credit(
            user      : $user,
            amount    : $amount,
            reference : $data['transfer_code'],
            comment   : 'Reversal: bank transfer failed — ' . ($data['reason'] ?? 'no reason provided'),
        );
    }
}
