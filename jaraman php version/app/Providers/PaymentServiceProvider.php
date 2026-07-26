<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\PaymentGateways\Paystack;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interface to concrete — swap Paystack::class for another gateway here
        $this->app->bind(PaymentGatewayInterface::class, Paystack::class);

        // Register Paystack as a singleton for direct injection
        $this->app->singleton(Paystack::class);
    }

    public function boot(): void {}
}
