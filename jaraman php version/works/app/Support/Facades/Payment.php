<?php

namespace App\Support\Facades;

use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\Facade;

class Payment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PaymentManager::class;
    }
}
