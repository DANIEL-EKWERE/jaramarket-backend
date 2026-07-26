<?php

namespace App\Enums\Account;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum AccountTransactionTypeEnum: string
{
    use InvokableCases, Names, Values;

    case debit = 'debit';
    case credit = 'credit';
}
