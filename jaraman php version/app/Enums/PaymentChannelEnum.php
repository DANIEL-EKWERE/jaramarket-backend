<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum PaymentChannelEnum: string
{
    use InvokableCases, Names, Values;

    case PAYSTACK    = 'paystack';
    case FLUTTERWAVE = 'flutterwave';
}
