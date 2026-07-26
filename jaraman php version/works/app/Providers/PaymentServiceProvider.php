<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\PaystackGateway;   // swap with your real implementation
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Bind the PaymentGatewayInterface to a concrete implementation.
     *
     * If you need to switch gateways (e.g. Flutterwave, Stripe) you only
     * change this one line — all controllers stay untouched.
     */
    public function register(): void
    {
        $this->app->bind(
            PaymentGatewayInterface::class,
            PaystackGateway::class   // ← replace with your concrete class
        );
    }

    public function boot(): void
    {
        //
    }
}
