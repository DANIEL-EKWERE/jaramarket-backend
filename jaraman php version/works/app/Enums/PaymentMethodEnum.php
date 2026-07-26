<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum PaymentMethodEnum: string
{
    use InvokableCases, Names, Values;

    case PAY_NOW = 'pay_now';
    case ONLINE  = 'online';
    case OFFLINE = 'offline';
}