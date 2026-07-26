<?php

namespace App\Services\Payment;

use App\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(public UserRepositoryInterface $userRepository) {}
}
