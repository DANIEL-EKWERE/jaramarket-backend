<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum TransactionStatusEnum: string
{
    use InvokableCases, Names, Values;

    case PENDING            = 'pending';
    case FAILED             = 'failed';
    case PAYMENT_SUCCESSFUL = 'success';
}
