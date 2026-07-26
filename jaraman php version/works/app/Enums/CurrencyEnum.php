<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum CurrencyEnum: string
{
    use InvokableCases, Values, Names;

    case NGN   = 'NGN';
    case USD  = 'USD';
}
