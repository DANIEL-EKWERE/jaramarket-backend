<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum AdvertTypeEnum: string
{
    use InvokableCases, Names, Values;

    case DISCOUNT = 'discount';
    case OFF      = 'off';
    case INFO     = 'info';
}