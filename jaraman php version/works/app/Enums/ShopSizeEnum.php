<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum ShopSizeEnum: string
{
    use InvokableCases, Values, Names;

    case JUST_ME     = 'just_me';
    case TWO_TO_FIVE = '2-5';
    case SIX_TO_TEN  = '6-10';
    case ELEVEN_PLUS = '11+';
}
