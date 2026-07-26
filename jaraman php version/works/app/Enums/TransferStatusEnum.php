<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum TransferStatusEnum: string
{
    use InvokableCases, Names, Values;

    case CREATED  = 'created';
    case PENDING  = 'pending';
    case SUCCESS  = 'success';
    case FAILED   = 'failed';
    case REVERSED = 'reversed';
}
