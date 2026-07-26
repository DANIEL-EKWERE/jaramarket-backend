<?php

namespace App\Enums\Sms;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum SmsGatewayEnum: string
{
    use InvokableCases, Values, Names;

    case TERMII = 'termii';
    case TWILIO = 'twilio';
}
