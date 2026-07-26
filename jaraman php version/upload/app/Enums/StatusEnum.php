<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum StatusEnum: string
{
    use InvokableCases, Values, Names;

    case PENDING    = 'pending';
    case SUCCESS    = 'success';
    case APPROVED   = 'approved';
    case ACCEPTED   = 'accepted';
    case CANCELLED  = 'cancelled';
    case CLOSED     = 'closed';
    case OPEN       = 'open';
    case INPROGRESS = 'in progress';
    case RESOLVED   = 'resolved';
    case BLOCKED    = 'blocked';
    case COMPLETED  = 'completed';
    case ACTIVE     = 'active';
    case PROCESSING = 'processing';
    case STOP       = 'stop';
    case FAILED     = 'failed';
}
