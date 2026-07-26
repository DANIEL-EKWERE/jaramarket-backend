<?php

namespace App\Http\Controllers\API;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Paystack\TransferToBankRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Services\WalletService;

class WalletController extends Controller
{
    public function __construct(protected WalletService $walletService) {}

    /** POST /wallet/transfer-to-bank */
    public function transfer(TransferToBankRequest $request)
    {
        try {
            $result = $this->walletService->transferToBank($request->user(), $request->validated());
            return response()->success('Withdrawal initiated successfully', $result, 200);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** GET /wallet — current balance */
    public function balance(Request $request)
    {
        try {
            return response()->success('Wallet retrieved', new WalletResource($request->user()->wallet), 200);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** GET /wallet/transactions?type=credit|debit&per_page=20 */
    public function transactions(Request $request)
    {
        try {
            $logs = $this->walletService->getTransactionHistory(
                $request->user(),
                (int) $request->get('per_page', 20),
                $request->get('type')
            );
            return response()->success('Transaction history retrieved successfully',
                TransactionResource::collection($logs)->response()->getData(true), 200);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
