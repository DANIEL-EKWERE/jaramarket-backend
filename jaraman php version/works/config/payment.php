<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'paystack'),
    'gateways' => [
        'paystack' => [],
        'flutterwave' => [],
        'stripe'  => [],
    ],
];
