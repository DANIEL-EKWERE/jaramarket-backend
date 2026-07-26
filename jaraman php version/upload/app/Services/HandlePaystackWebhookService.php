<?php

namespace App\Services;

use App\Exceptions\GeneralException;

class HandlePaystackWebhookService
{
    public function __construct(
        protected HandleChargeSuccessService   $chargeSuccess,
        protected HandleTransferSuccessService $transferSuccess,
        protected HandleTransferFailedService  $transferFailed,
    ) {}

    public function handleWebhook(array $data): void
    {
        match ($data['event']) {
            'charge.success'   => $this->chargeSuccess->handleWebhook($data),
            'transfer.success' => $this->transferSuccess->handleWebhook($data),
            'transfer.failed'  => $this->transferFailed->handleWebhook($data),
            'charge.failed'    => logger()->info('Paystack charge.failed', ['data' => $data]),
            default            => throw new GeneralException("Unknown webhook event: {$data['event']}", 400),
        };
    }
}
