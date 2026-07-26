<?php

namespace App\Contracts;

interface SmsGatewayInterface
{
    /**
     * @return mixed
     *
     * @throws GeneralException
     */
    public function send(string $to, string $content);
}
