<?php

namespace App\Services\Payment;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractPaymentGateway
{
    public function setTransactionInitiator(Model $model)
    {
        $this->initiator_type = $model->getMorphClass();
        $this->initiator_id = $model->id;

        return $this;
    }

    public function setTransactionOwner(Model $model)
    {
        $this->owner_type = $model->getMorphClass();
        $this->owner_id = $model->id;

        return $this;
    }

    abstract public function initializeTransaction(string $email, int|float $amount, string $currency, string $callback_url, array $metadata = [], array $custom_fields = []);

    abstract public function verifyTransaction(string $reference);

    abstract public function updateTransactionStatus(string $reference);

    abstract public function updateTransactionStatusFromWebHook(array $data);

    abstract public function chargeAuthorization($amount, $email, $authorization, $currency = 'NGN', $metadata = []);

    abstract public function cancelSubscription($code, $token);

    abstract public function transfer(string $recipient_code, float|int $amount);
}
