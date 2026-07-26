<?php

namespace App\Macros;

use Illuminate\Support\Facades\Http;

class HttpMacro
{
    public function paystack()
    {
        return function () {
            $url    = config('app.paystack_url', 'https://api.paystack.co');
            $secret = config('app.paystack_secret_key');

            if (empty($secret)) {
                throw new \RuntimeException(
                    'Paystack secret key is not configured. Set PAYSTACK_SECRET_KEY in your .env file.'
                );
            }

            return Http::withToken($secret)
                ->withHeaders(['content-type' => 'application/json'])
                ->baseUrl(rtrim($url, '/'));
        };
    }

    public function termii()
    {
        return function () {
            $url = config('app.termii_base_url', 'https://api.ng.termii.com/api/');

            return Http::withHeaders(['content-type' => 'application/json'])
                ->baseUrl(rtrim($url, '/'));
        };
    }

    public function flutterwave()
    {
        return function () {
            $url    = config('app.flutterwave_url', 'https://api.flutterwave.com/v3');
            $secret = config('app.flutterwave_secret_key');

            return Http::withToken($secret ?? '')
                ->withHeaders(['content-type' => 'application/json'])
                ->baseUrl(rtrim($url, '/'));
        };
    }
}
