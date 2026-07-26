<?php

namespace App\Enums\Prefix;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum PrefixEnum: string
{
    use InvokableCases, Values, Names;

    case BONUS_CODE = 'IPC_';
    case REFERAL_CODE = 'REF_';
    case ACCOUNT_REF = 'ACC_';
    case WALLET_REF = 'WLT_';
    case SUBSCRIPTION_TXN = 'JARASUP_';
    case DEPOSIT_TXN = 'JARA_';
    case ORDER_REF = 'JARA_ORD_';
}
