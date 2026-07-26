<?php

namespace App\Http\Controllers\API;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Paystack\VerifyPaystackRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\TransferResource;
use App\Models\PaymentLog;
use App\Models\User;
use App\Services\HandlePaystackWebhookService;
use App\Services\PaymentGateways\Paystack;
use App\Enums\TransactionStatusEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    public function __construct(
        protected Paystack                      $paystack,
        protected HandlePaystackWebhookService  $webhookService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | POST /jaram/webhook/paystack  (public — verified by middleware)
    |--------------------------------------------------------------------------
    */
    public function handlePaystackWebhook(VerifyPaystackRequest $request): JsonResponse
    {
        try {
            $this->webhookService->handleWebhook($request->all());
            return response()->json(['status' => true]);
        } catch (Exception $e) {
            report($e);
            // Always 200 to Paystack — prevents endless retries
            return response()->json(['status' => false, 'message' => 'Processed with errors.']);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /jaram/verify-transaction/{reference}
    | Call this after Paystack redirect to confirm payment
    |--------------------------------------------------------------------------
    */
    public function verifyTransaction(string $reference): JsonResponse
    {
        try {
            $data = $this->paystack->verifyTransaction($reference);
            return response()->success('Transaction verified.', $data);
        } catch (GeneralException $e) {
            return response()->errorResponse($e->getMessage(), [], $e->getCode() ?: 400);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse('Unable to verify transaction.', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /jaram/payments
    |--------------------------------------------------------------------------
    */
    public function all(Request $request): JsonResponse
    {
        $transactions = PaymentLog::with('initiator', 'owner')
            ->when($request->boolean('is_admin'), function ($q) use ($request) {
                if ($request->filled('user_id')) {
                    $q->whereHasMorph('initiator', [User::class], fn ($q) =>
                        $q->where('id', $request->user_id)
                    );
                }
            }, function ($q) {
                $q->whereHasMorph('initiator', [User::class], fn ($q) =>
                    $q->where('id', auth()->id())
                )->where('status', TransactionStatusEnum::PAYMENT_SUCCESSFUL());
            })
            ->latest()
            ->paginate(config('constants.pagination_count', 20));

        return TransactionResource::collection($transactions)
            ->additional(['status' => true, 'message' => 'Transactions retrieved.'])
            ->response();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /jaram/payments/{id}
    |--------------------------------------------------------------------------
    */
    public function show(int $id): JsonResponse
    {
        try {
            $transaction = PaymentLog::with('initiator', 'owner')
                ->when(! request()->boolean('is_admin'), function ($q) {
                    $q->whereHasMorph('initiator', [User::class], fn ($q) =>
                        $q->where('id', auth()->id())
                    )->where('status', TransactionStatusEnum::PAYMENT_SUCCESSFUL());
                })
                ->findOrFail($id);

            return response()->success('Payment retrieved.', new TransactionResource($transaction));
        } catch (Exception $e) {
            return response()->errorResponse('Payment not found.', [], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /jaram/transfers
    |--------------------------------------------------------------------------
    */
    public function getTransfers(Request $request): JsonResponse
    {
        $user      = $request->user();
        $transfers = $user->transfers()->orderByDesc('created_at')->paginate(15);
        $total     = $user->transfers()->sum('amount');

        return response()->success('Transfers retrieved.', [
            'transfers'    => TransferResource::collection($transfers),
            'total_amount' => number_format($total, 2),
            'pagination'   => [
                'total'        => $transfers->total(),
                'per_page'     => $transfers->perPage(),
                'current_page' => $transfers->currentPage(),
                'last_page'    => $transfers->lastPage(),
            ],
        ]);
    }
}
