<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum WalletTransactionTypeEnum: string
{
    use InvokableCases, Names, Values;

    case CREDIT = 'credit';
    case DEBIT  = 'debit';
}