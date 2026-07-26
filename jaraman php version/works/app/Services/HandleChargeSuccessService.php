<?php

namespace App\Services;

use App\Utils\Util;
use App\Models\User;
use App\Models\Admin;
use App\Notifications\FailedPaymentNotification;

class HandleChargeSuccessService
{
     public function __construct(
        public TransactionService $transactionService
    ) {}

    public function handleWebhook($data)
    {
        $data = $data['data'];
        $email = match (Util::isEmailModified($data['customer']['email'])) {
            true => Util::getOriginalEmail($data['customer']['email']),
            false => $data['customer']['email']
        };

        $user = User::where('email', $email)->first();
        $admin = Admin::first();

        if (! $user && ! $admin) {
            $admin->notify( new FailedPaymentNotification($email, $data));
            return;
        }

        $this->transactionService->checkDuplicateTransaction($data['reference']);

        $this->transactionService->updateTransaction($data, $user);
    }
}
