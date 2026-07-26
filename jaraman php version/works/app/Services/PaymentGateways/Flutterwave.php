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

class Flutterwave extends AbstractPaymentGateway implements PaymentGatewayInterface
{
    const SUCCESS = 'successful';

    const PENDING = 'pending';

    const FAILED = 'failed';

    protected $initiator_type;

    protected $initiator_id;

    protected $owner_type;

    protected $owner_id;

    public function verifyTransaction(string $reference)
    {
        $transaction = Http::flutterwave()->get('transactions/verify_by_reference', [
            'tx_ref' => $reference,
        ])->json();

        if (! data_get($transaction, 'status')) {
            throw new GeneralException(data_get($transaction, 'message'), Response::HTTP_BAD_REQUEST);
        }

        $log = PaymentLog::where('txn_ref', data_get($transaction, 'data.tx_ref'))->first();
        $status = data_get($transaction, 'data.status');

        if ($log->status != $status) {
            if ($status === self::SUCCESS) {
                TransactionStatusUpdateJob::dispatch($transaction);
            } else {
                $log->update(['status' => $status]);
            }
        }

        if (data_get($transaction, 'data.status') === self::FAILED) {                                   // Confirm the value for failed transactions
            throw new GeneralException('Payment unsuccessful', Response::HTTP_BAD_REQUEST);
        }

        return data_get($transaction, 'data');
    }

    public function setTransactionInitiator(Model $initiator)
    {
        $this->initiator_type = get_class($initiator);
        $this->initiator_id = $initiator->getKey();

        return $this;
    }

    public function setTransactionOwner(Model $owner)
    {
        $this->owner_type = get_class($owner);
        $this->owner_id = $owner->getKey();

        return $this;
    }

    public function initializeTransaction(string $email, int|float $amount, string $currency = 'NGN', ?string $callback = null, array $channels = [], array $metadata = [], ?string $plan = null)
    {
        if (is_null($this->initiator_type) || is_null($this->initiator_id)) {
            throw new GeneralException('Transaction initiator not set', 500);
        }

        $reference = Util::generate_sub_txn_ref();

        $data = [
            'tx_ref' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'meta' => $metadata,
            'customer' => [
                'email' => $email,
            ],
            'redirect_url' => $callback,
            'payment_options' => match (empty($channels)) {
                true => ['card', 'account', 'ussd', 'barter', 'banktransfer', 'nqr'],
                false => $channels,
            },
        ];

        $transaction = Http::flutterwave()->post('payments', $data)->json();

        if (! data_get($transaction, 'status')) {
            throw new GeneralException(data_get($transaction, 'message'), Response::HTTP_BAD_REQUEST);
        }

        $txn_data = [
            'txn_ref' => $reference,
            'authorization_url' => data_get($transaction, 'data.link'),
            'amount' => $amount,
            'meta' => json_encode($metadata),
            'status' => self::PENDING,
            'transaction_owner_id' => $this->owner_id,
            'transaction_owner_type' => $this->owner_type,
            'transaction_initiator_id' => $this->initiator_id,
            'transaction_initiator_type' => $this->initiator_type,
            'provider' => class_basename($this),
            'plan' => $plan,
        ];

        LogActivity::logTransaction($txn_data);

        return $transaction['data'];
    }

    public function updateTransactionStatus(string $reference)
    {
        $transaction = PaymentLog::firstWhere('txn_ref', $reference);

        if (is_null($transaction)) {
            throw new GeneralException('Transaction not found', Response::HTTP_NOT_FOUND);
        }

        $verifyTransaction = $this->verifyTransaction($reference);

        $transaction->update([
            'status' => $verifyTransaction['status'] === self::SUCCESS ? self::SUCCESS : self::FAILED,
            'gateway_response' => $verifyTransaction['processor_response'],
        ]);

        return $transaction;
    }

    public function updateTransactionStatusFromWebhook(array $data)
    {
        $transaction = PaymentLog::firstWhere('txn_ref', data_get($data, 'tx_ref'));

        if (is_null($transaction)) {
            throw new GeneralException('Transaction not found', Response::HTTP_NOT_FOUND);
        }

        $verifyTransaction = $this->verifyTransaction(data_get($data, 'tx_ref'));

        $transaction->update([
            'status' => $verifyTransaction['status'] === self::SUCCESS ? self::SUCCESS : self::FAILED,
            'gateway_response' => $verifyTransaction['processor_response'],
        ]);

        return $transaction;
    }

    public function getSubscriptionByCode(string $code)
    {
        return Http::get('subscriptions', [
            'id' => $code,
        ]);
    }

    public function chargeAuthorization($amount, $email, $authorization, $currency = 'NGN', $metadata = [])
    {
        return Http::flutterwave()->post('transactions/tokenized-charges', [
            'token' => $authorization,
            'currency' => $currency,
            'amount' => $amount,
            'email' => $email,
            'tx_ref' => Util::generate_sub_txn_ref(),
        ]);
    }

    public function cancelSubscription($code, $token)
    {
        return Http::flutterwave()->post('subscriptions/'.$code.'/cancel');
    }

    public function sendUpdateCardEmail(string $subscriptionCode)
    {
        //        return Http::paystack()->post('subscription/'.$subscriptionCode.'/manage/email');
    }

    public function getUpdateCardLink(string $subscriptionCode)
    {
        //        return Http::paystack()->post('subscription/'.$subscriptionCode.'/manage/link');
    }

    public function subscribe($user, $planCode)
    {
        //        return Http::flutterwave()->post('subscription', [
        //            'customer' => $user->email,
        //            'plan' => $planCode,
        //        ]);
    }

    public function resolveAccountNumber($accountNumber, $bankCode)
    {
        return Http::flutterwave()->get('/accounts/resolve', [
            'account_number' => $accountNumber,
            'account_bank' => $bankCode,
        ]);
    }

    public function createPlan(string $name, string $interval, int $amount, ?int $limit = null, $currency = 'NGN')
    {
        return Http::flutterwave()->post('payment-plans', [
            'name' => $name,
            'interval' => $interval,
            'amount' => $amount,
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

        $transfer_log = Transfer::create([
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

        return $transfer_log;
    }

    public function createTransferRecipient(BankAccount|array $bank_account)
    {
        $data = array_filter([
            'account_bank' => data_get($bank_account, 'bank_code'),
            'account_number' => data_get($bank_account, 'account_number'),
            'beneficiary_name' => data_get($bank_account, 'account_name'),
            'bank_name' => data_get($bank_account, 'bank_name'),
        ]);

        $recipient = Http::flutterwave()->post('/beneficiaries', $data)->json();

        if (! data_get($recipient, 'status')) {
            throw new GeneralException($create_transfer_recipient['message'] ?? 'Unable to create transfer recipient');
        }

        return $recipient;
    }

    public function transfer(string $recipient_code, float|int $amount, ?string $reason = null, $reference = null)
    {
        return Http::flutterwave()->post('transfers', [
            'narration' => $reason ?? 'Appointment booking payment',
            'amount' => $amount,
            'beneficiary' => $recipient_code,
            'reference' => $reference ?? Util::generate_sub_txn_ref(),
            'currency' => 'NGN',
        ])->json();
    }

    public function virtualAccount(string $first_name, string $last_name, $email)
    {
        return Http::flutterwave()->post('virtual-account-numbers', [
            'email' => $email,
            'is_permanent' => false,
            'firstname' => $first_name,
            'lastname' => $last_name,
            'narration' => "General / $first_name $last_name",
        ])->json();
    }

    public function logTransfer($transfer, $recipient_code, $bank_account, $owner_id, $owner_type, $amount)
    {
        return Transfer::create([
            'amount' => $amount,
            'reference' => $transfer['data']['reference'],
            'recipient_code' => $recipient_code,
            'owner_id' => $owner_id,
            'owner_type' => $owner_type,
            'account_number' => $bank_account->account_number,
            'account_name' => $bank_account->account_name,
            'bank_code' => $bank_account->bank_code,
            'bank_name' => $bank_account->bank_name,
        ]);
    }

    public function verifyTransfer(string $reference)
    {
        return Http::flutterwave()->get("transfers/{$reference}")->json();
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function bankTransfer(float $amount, string $bankCode, string $accountNumber, string $reference, string $narration, string $currency = 'NGN')
    {
        //TODO bankTransfer;
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
            'account_name' => data_get($bankDetails, 'account_name'),
        ];

        $transferRecipient = $this->createTransferRecipient($recipientData);

        return data_get($transferRecipient, 'data.id');
    }
}
