<?php

namespace App\Services\PaymentGateways;

use App\Models\User;
use App\Contracts\PaymentGatewayInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class PaymentBridge
{
    protected PaymentGatewayInterface $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function setTransactionInitiator(User|Authenticatable|null $user): PaymentGatewayInterface
    {
        return $this->paymentGateway->setTransactionInitiator($user);
    }

    public function initiateTransaction(string $email, int|float $amount, string $currency = 'NGN', ?string $callback = null, array $channels = [], array $metadata = [], ?string $plan = null)
    {
        return $this->paymentGateway->initializeTransaction($email, $amount, $currency = 'NGN', $callback = null, $channels = [], $metadata = [], $plan = null);
    }

    public function verifyTransaction(string $reference)
    {
        return $this->paymentGateway->verifyTransaction($reference);
    }
}
