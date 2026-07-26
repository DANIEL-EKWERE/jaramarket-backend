<?php

namespace App\Services;

use App\Services\HandleChargeSuccessService;
use App\Services\HandleTransferSuccessService;
use App\Services\HandleTransferFailedService;
use App\Exceptions\GeneralException;

class HandlePaystackWebhookService
{
    public function __construct(
        public HandleChargeSuccessService $handleChargeSuccessService, 
        public HandleTransferSuccessService $handleTransferSuccessService,
        public HandleTransferFailedService $handleTransferFailedService     
    ) {}

    public function handleWebhook(array $data)
    {
        return match ($data['event']) {
            'charge.success'   => $this->handleChargeSuccessService->handleWebhook($data), 
            'transfer.success' => $this->handleTransferSuccessService->handleWebhook($data),            
            'transfer.failed' => $this->handleTransferFailedService->handleWebhook($data),
            'charge.failed' => info('Payment Failed'),
            default => throw new GeneralException('Unknown webhook type', 400),
        };
    }
}
