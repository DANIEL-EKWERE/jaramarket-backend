<?php

namespace App\Enums\Account;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum PaymentProviderEnum: string
{
    use InvokableCases, Names, Values;

    case paystack = 'Paystack';
    case manual = 'Manual';
    case flutterwave = 'Flutterwave';
    case piggyvest = 'Piggyvest';
}
