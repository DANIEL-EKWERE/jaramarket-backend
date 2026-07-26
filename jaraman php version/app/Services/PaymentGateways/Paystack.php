<?php

namespace App\Services\PaymentGateways;

use Throwable;
use App\Utils\Util;
use App\Models\Transfer;
use App\Models\PaymentLog;
use App\Models\BankAccount;
use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Support\Facades\LogActivity;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use App\Jobs\TransactionStatusUpdateJob;
use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\AbstractPaymentGateway;
use App\Services\ServiceProvider\ProviderBankAccountService;

class Paystack extends AbstractPaymentGateway implements PaymentGatewayInterface
{
    const SUCCESS = 'success';

    const PENDING = 'pending';

    const FAILED = 'failed';

    const ABANDONED = 'abandoned';

    protected $initiator_type;

    protected $initiator_id;

    protected $owner_type;

    protected $owner_id;

    /**
     * @return mixed
     *
     * @throws GeneralException
     */
    public function verifyTransaction(string $reference)
    {
        $transaction = Http::paystack()->get('/transaction/verify/'.$reference)->json();
        if (! $transaction['status']) {
            throw new GeneralException($transaction['message'], Response::HTTP_BAD_REQUEST);
        }
        $log = PaymentLog::where('txn_ref', $transaction['data']['reference'])->first();
        $status = $transaction['data']['status'];
        if ($log->status != $status) {
            if ($status === self::SUCCESS) {
                TransactionStatusUpdateJob::dispatch($transaction);
            } else {
                $log->update(['status' => $status]);
            }
        }
        if ($transaction['data']['status'] === self::FAILED) {
            throw new GeneralException('Payment unsuccessful', Response::HTTP_BAD_REQUEST);
        }

        return $transaction['data'];
    }

    /**
     * @return $this
     */
    public function setTransactionInitiator(Model $initiator)
    {
        $this->initiator_type = get_class($initiator);
        $this->initiator_id = $initiator->getKey();

        return $this;
    }

    /**
     * @return $this
     */
    public function setTransactionOwner(Model $owner)
    {
        $this->owner_type = get_class($owner);
        $this->owner_id = $owner->getKey();

        return $this;
    }

    /**
     * Initializes a transaction and generates a checkout url
     *
     * @return mixed
     *
     * @throws GeneralException
     */
    public function initializeTransaction(string $email, int|float $amount, string $currency = 'NGN', ?string $callback = null, array $channels = [], array $metadata = [], ?string $plan = null)
    {
        if (is_null($this->initiator_type) || is_null($this->initiator_id)) {
            throw new GeneralException('Transaction initiator not set', 500);
        }

        if (empty($channels)) {
            $channels = ["card", "bank", "ussd", "qr", "mobile_money", "eft"];
        }

        $reference = Util::generate_sub_txn_ref();

        $payload = [
            'channels' => $channels,
            'metadata' => json_encode($metadata),
            'email' => $email,
            'reference' => $reference,
            'amount' => Util::convert_amount_by_currency($amount, $currency),
            'currency' => $currency,
            'callback_url' => $callback,
            'plan' => $plan,
        ];

        $transaction = Http::paystack()->post('/transaction/initialize', $payload)->json();

        if ($transaction['status'] === false) {
            throw new GeneralException($transaction['message'], 400);
        }

        $meta = (array) $metadata;
        $plan_code = isset($meta['plan_code']) ? $meta['plan_code'] : null;
        $post_id   = isset($meta['post_id']) ? $meta['post_id'] : null;

        $txn_data = [
            'txn_ref' => $reference,
            'authorization_url' => $transaction['data']['authorization_url'] ?? null,
            'amount' => $amount,
            'meta' => json_encode($metadata),
            'status' => self::PENDING,
            'transaction_owner_id' => $this->owner_id,
            'transaction_owner_type' => $this->owner_type,
            'transaction_initiator_id' => $this->initiator_id,
            'transaction_initiator_type' => $this->initiator_type,
            'provider' => class_basename($this),
            'plan' =>  $plan_code,
            'post' =>  $post_id
        ];

        LogActivity::logTransaction($txn_data);

        return $transaction['data'];
    }

    /**
     * @return mixed
     *
     * @throws GeneralException
     */
    public function updateTransactionStatus(string $reference)
    {
        $transaction = PaymentLog::firstWhere('txn_ref', $reference);
        if (! $transaction) {
            throw new GeneralException('No transaction found for the given reference', 404);
        }
        $verify_transaction = $this->verifyTransaction($reference);
        $transaction->update([
            'status' => $verify_transaction['status'],
            'gateway_response' => $verify_transaction['gateway_response'],
        ]);

        return $transaction;
    }

    /**
     * @param  array  $array
     * @return mixed
     *
     * @throws GeneralException
     */
    public function updateTransactionStatusFromWebhook(array $data)
    {
        $transaction = PaymentLog::firstWhere('txn_ref', $data['reference']);
        if (! $transaction) {
            throw new GeneralException('No transaction found for the given reference', 404);
        }
        $transaction->update([
            'status' => $data['status'],
            'gateway_response' => $data['gateway_response'],
        ]);

        return $transaction;
    }

    public function getSubscriptionByCode($subscriptionCode)
    {
        return Http::paystack()->get('subscription/'.$subscriptionCode);
    }

    public function chargeAuthorization($amount, $email, $authorization, $currency = 'NGN', $metadata = [])
    {
        return Http::paystack()->post('transaction/charge_authorization', [
            'amount' => Util::convert_amount_by_currency($amount, $currency),
            'email' => $email,
            'authorization_code' => $authorization,
            'metadata' => json_encode($metadata),
        ]);
    }

    public function cancelSubscription($code, $token)
    {
        return Http::paystack()->post('subscription/disable', [
            'code' => $code,
            'token' => $token,
        ]);
    }

    public function sendUpdateCardEmail(string $subscriptionCode)
    {
        return Http::paystack()->post('subscription/'.$subscriptionCode.'/manage/email');
    }

    public function getUpdateCardLink(string $subscriptionCode)
    {
        return Http::paystack()->post('subscription/'.$subscriptionCode.'/manage/link');
    }

    public function subscribe($user, $planCode)
    {
        return Http::paystack()->post('subscription', [
            'customer' => $user->email,
            'plan' => $planCode,
        ]);
    }

    public function resolveAccountNumber($accountNumber, $bankCode)
    {
        return Http::paystack()->get('bank/resolve', [
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
        ]);
    }

    public function createPlan(string $name, string $interval, int $amount, ?int $limit = null, $currency = 'NGN')
    {
        return Http::paystack()->post('plan', [
            'name' => $name,
            'interval' => $interval,
            'amount' => Util::convert_amount_by_currency($amount, $currency),
            'invoice_limit' => $limit,
        ]);
    }

    public function payout(BankAccount $bank_account, int $amount, int $owner_id, string $owner_type)
    {
        $create_transfer_recipient = $this->createTransferRecipient($bank_account);

        $recipient_code = $create_transfer_recipient['data']['recipient_code'];

        $transfer = $this->transfer($recipient_code, $amount);

        if (! $transfer['status']) {
            throw new GeneralException($transfer['message'] ?? 'Something went wrong while trying to transfer');
        }

        return $this->logTransfer($transfer, $recipient_code, $bank_account, $owner_id, $owner_type, $amount);
    }

    public function logTransfer($transfer, $recipient_code, $bank_account, $owner_id, $owner_type, $amount)
    {
        return Transfer::create([
            'amount' => $amount,
            'reference' => $transfer['data']['transfer_code'],
            'recipient_code' => $recipient_code,
            'owner_id' => $owner_id,
            'owner_type' => $owner_type,
            'account_number' => $bank_account->account_number,
            'account_name' => $bank_account->account_name,
            'bank_code' => $bank_account->bank_code,
            'bank_name' => $bank_account->bank_name,
        ]);
    }

    public function createTransferRecipient(BankAccount|array $bank_account)
    {
        $recipient = Http::paystack()->post('transferrecipient', [
            'type' => 'nuban',
            'name' => data_get($bank_account, 'account_name'),
            'account_number' => data_get($bank_account, 'account_number'),
            'bank_code' => data_get($bank_account, 'bank_code'),
            'currency' => 'NGN',
        ])->json();

        if (! $recipient['status']) {
            throw new GeneralException($create_transfer_recipient['message'] ?? 'Unable to create transfer recipient');
        }

        return $recipient;
    }

    public function transfer(string $recipient_code, float|int $amount, ?string $reason = null, ?string $reference = null)
    {
        $data = array_filter([
            'source' => 'balance',
            'reason' => $reason ?? 'Transfer',
            'amount' => Util::convert_amount_by_currency($amount, 'NGN'),
            'recipient' => $recipient_code,
            'reference' => $reference,
        ]);

        return Http::paystack()->post('transfer', $data)->json();
    }

    public function virtualAccount(string $first_name, string $last_name, $email)
    {
        // TODO: Implement virtualAccount() method.
    }

    public function verifyTransfer(string $reference)
    {
        return Http::paystack()->get("transfer/verify/{$reference}")->json();
    }

    /**
     * @throws GeneralException|Throwable
     */
    public function bankTransfer(float $amount, string $bankCode, string $accountNumber, string $reference, string $narration, string $currency = 'NGN'): ?array
    {
        // TODO: Implement bankTransfer() method.
    }

    /**
     * @throws GeneralException
     */
    private function getRecipientCode(string $bankCode, string $accountNumber)
    {
        $bankDetails = $this->resolveAccountNumber($accountNumber, $bankCode);
        $recipientData = [
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
            'account_name' => data_get($bankDetails, 'data.account_name'),
        ];
        $transferRecipient = $this->createTransferRecipient($recipientData);

        return data_get($transferRecipient, 'data.recipient_code');
    }

    public function initializeCustomerAuthorization(string $email, string $callback_url, string $channel = 'direct_debit')
    {
        return Http::paystack()
            ->post('/customer/authorization/initialize', compact('email', 'callback_url', 'channel'))
            ->throw()
            ->json();
    }
}
