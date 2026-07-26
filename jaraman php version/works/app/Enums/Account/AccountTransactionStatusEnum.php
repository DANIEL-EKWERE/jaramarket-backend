<?php

namespace App\Enums\Account;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum AccountTransactionStatusEnum: string
{
    use InvokableCases, Names, Values;

    case pending = 'pending';
    case completed = 'completed';
    case failed = 'failed';
}
