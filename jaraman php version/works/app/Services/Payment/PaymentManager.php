<?php

namespace App\Services\Payment;

use App\Exceptions\GeneralException;
use App\Services\PaymentGateways\Flutterwave;
use App\Services\PaymentGateways\Paystack;
use Illuminate\Http\Response;

class PaymentManager
{
    protected string $gateway;

    public function __construct()
    {
        $this->gateway = config('payment.default');
    }

    public function gateway(?string $gateway = null)
    {
        $gateway = $gateway ?? $this->gateway;

        throw_if(
            ! in_array($gateway, array_keys(config('payment.gateways'))),
            new GeneralException("Invalid payment gateway: {$gateway}", Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        $this->gateway = $gateway;

        return $this;
    }

    public function __call(string $name, array $arguments)
    {
        $paymentGateway = 'create'.ucfirst($this->gateway).'Payment';

        if (! method_exists($this, $paymentGateway)) {
            throw new GeneralException("Invalid payment gateway: {$this->gateway}", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->{$paymentGateway}()->{$name}(...$arguments);
    }

    public function createPaystackPayment()
    {
        return new Paystack();
    }

    public function createFlutterwavePayment()
    {
        return new Flutterwave();
    }
}
