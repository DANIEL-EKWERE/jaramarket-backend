<?php

namespace App\Contracts;

use App\Exceptions\GeneralException;
use Illuminate\Database\Eloquent\Model;

interface PaymentGatewayInterface
{
    /**
     * @return mixed
     *
     * @throws GeneralException
     */
    public function verifyTransaction(string $reference);

    /**
     * @return $this
     */
    public function setTransactionInitiator(Model $initiator);

    /**
     * @return $this
     */
    public function setTransactionOwner(Model $owner);

    /**
     * Initializes a transaction and generates a checkout url
     *
     * @return mixed
     *
     * @throws GeneralException
     */
    public function initializeTransaction(string $email, int|float $amount, string $currency = 'NGN', ?string $callback = null, array $channels = [], array $metadata = [], ?string $plan = null);

    /**
     * @return mixed
     *
     * @throws GeneralException
     */
    public function updateTransactionStatus(string $reference);

    /**
     * @param  array  $array
     * @return mixed
     *
     * @throws GeneralException
     */
    public function updateTransactionStatusFromWebhook(array $data);

    public function transfer(string $recipient_code, float|int $amount, ?string $reason = null);

    public function logTransfer($transfer, $recipient_code, $bank_account, $owner_id, $owner_type, $amount);

    public function verifyTransfer(string $reference);

    public function bankTransfer(float $amount, string $bankCode, string $accountNumber, string $reference, string $narration, string $currency = 'NGN');
}
