<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum OrderNotificationTypeEnum: string
{
    use InvokableCases, Names, Values;

    case CUSTOMER  = 'customer';
    case VENDOR    = 'vendor';
    case ADMIN     = 'admin';
}