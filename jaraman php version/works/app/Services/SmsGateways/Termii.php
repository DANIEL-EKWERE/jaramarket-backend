<?php

namespace App\Services\SmsGateways;

use App\Contracts\SmsGatewayInterface;
use App\Exceptions\GeneralException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class Termii implements SmsGatewayInterface
{
    public function send(string $to, string $content, string $type = 'plain', string $channel = 'dnd')
    {
        if (app()->isLocal()) {
            return;
        }

        $payload = [
            'api_key' => config('app.termii_api_key'),
            'to' => $to,
            'from' => 'N-Alert',
            'sms' => $content,
            'type' => $type,
            'channel' => $channel,
        ];
        $response = Http::termii()->post('/sms/send', $payload);
        if (! $response->successful()) {
            info($response->json());
            throw new GeneralException($response['message'] ?? 'Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response->json();
    }

    public function checkDND(string $phone)
    {
        $api_key = config('app.termii_api_key');
        $response = Http::termii()->get("/check/dnd?api_key={$api_key}&phone_number={$phone}");

        return $response->json();
    }
}
