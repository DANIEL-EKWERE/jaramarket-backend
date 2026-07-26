<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum DeliveryTypeEnum: string
{
    use InvokableCases, Names, Values;

    case PICKUP    = 'pickup';
    case DELIVERY  = 'delivery';
    case WALKIN    = 'walkin';
}