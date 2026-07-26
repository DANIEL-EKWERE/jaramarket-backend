<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum PinTypeEnum: string
{
    use InvokableCases, Names, Values;

    case SETUP             = 'setup';
    case UPDATED           = 'updated';
    case RESET_REQUEST     = 'reset_request';
    case REQUEST_CONFIRMED = 'reset_confirmed';
    case TOKEN_VALIDATED   = 'token_validated';
    case TOKEN_INVALID     = 'token_invalid';
    case TOKEN_CLEARED     = 'token_cleared';
}