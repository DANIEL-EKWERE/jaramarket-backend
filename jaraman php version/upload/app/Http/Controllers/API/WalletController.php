<?php

namespace App\Http\Controllers\API;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FundWalletRequest;
use App\Http\Requests\Paystack\TransferToBankRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WalletController extends Controller
{
    public function __construct(protected WalletService $walletService) {}

    /*
    |--------------------------------------------------------------------------
    | GET /jaram/wallet
    |--------------------------------------------------------------------------
    */
    public function balance(Request $request): JsonResponse
    {
        try {
            $wallet = $request->user()->wallet;

            if (! $wallet) {
                return response()->errorResponse('Wallet not found.', [], 404);
            }

            return response()->success('Wallet retrieved', new WalletResource($wallet));
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse('Unable to retrieve wallet.', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /jaram/payments/initialize-transaction
    | Body: { amount, currency, callback_url, payment_gateway, metadata? }
    |--------------------------------------------------------------------------
    */
    public function initializeFunding(FundWalletRequest $request): JsonResponse
    {
        try {
            $url = $this->walletService->initializeFunding(
                $request->user(),
                $request->validated()
            );

            return response()->success('Payment initialized. Redirect user to the URL to complete payment.', [
                'url' => $url,
            ]);
        } catch (GeneralException $e) {
            return response()->errorResponse($e->getMessage(), [], $e->getCode() ?: 400);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse('Unable to initialize payment.', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /jaram/wallet/transfer-to-bank
    | Body: { amount, bank_id, currency?, remark? }
    |--------------------------------------------------------------------------
    */
    public function transferToBank(TransferToBankRequest $request): JsonResponse
    {
        try {
            $result = $this->walletService->transferToBank(
                $request->user(),
                $request->validated()
            );

            return response()->success('Withdrawal initiated. Funds will arrive within a few minutes.', $result);
        } catch (GeneralException $e) {
            return response()->errorResponse($e->getMessage(), [], $e->getCode() ?: 400);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse('Unable to process withdrawal. Please try again.', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /jaram/wallet/transactions?type=credit|debit&per_page=20
    |--------------------------------------------------------------------------
    */
    public function transactions(Request $request): JsonResponse
    {
        $request->validate([
            'type'     => 'nullable|in:credit,debit',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $logs = $this->walletService->getTransactionHistory(
                user    : $request->user(),
                perPage : (int) $request->get('per_page', 20),
                type    : $request->get('type'),
            );

            return response()->success(
                'Transaction history retrieved.',
                TransactionResource::collection($logs)->response()->getData(true)
            );
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse('Unable to retrieve transactions.', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
