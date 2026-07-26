<?php

namespace App\Enums\Account;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum AccountSyncStatusEnum: string
{
    use InvokableCases, Names, Values;

    case COMPLETED = 'completed';
    case PENDING = 'pending';
}
