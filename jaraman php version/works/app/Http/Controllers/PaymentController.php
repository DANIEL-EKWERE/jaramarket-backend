<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransferResource;
use Exception;
use Throwable;
use App\Utils\Util;
use App\Models\User;
use App\Models\PaymentLog;
use App\Models\PaymentNow;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Support\Facades\Payment;
use App\Enums\TransactionStatusEnum;
use App\Exceptions\GeneralException;
use App\Http\Requests\FundWalletRequest;
use App\Services\Payment\PaymentService;
use App\Contracts\PaymentGatewayInterface;
use App\Http\Resources\TransactionResource;
use App\Services\HandlePaystackWebhookService;
use App\Http\Requests\Paystack\VerifyPaystackRequest;
use App\Models\Transfer;

class PaymentController extends Controller
{
    public function __construct(
        public PaymentGatewayInterface $paymentGateway,
        public HandlePaystackWebhookService $handlePaystackWebhookService,
        public PaymentService $paymentService
    ) {}

    
    public function index()
    {
        $payments = PaymentLog::with('user')
            ->latest('created_at')
            ->paginate(25);

        return view('payments.index', compact('payments'));
    }

    public function filter(Request $request)
    {
        $query = PaymentNow::with(['user', 'order']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $payments = $query->latest('payment_date')->paginate(10);
        return view('payments.index', compact('payments'));
    }

    public function export(Request $request)
    {
        $payments = PaymentNow::with(['user', 'order'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('payment_method'), function ($query) use ($request) {
                $query->where('payment_method', $request->payment_method);
            })
            ->get();

        return response()->json($payments);
    }

    public function fundWallet(FundWalletRequest $request)
    {
        try {
            $data = $request->validated();
            $payment_link = $this->initialize_payment($data);
            return response()->success('Payment is initialized successfully, here is the payment link generated', ['url' => $payment_link]);
            
        } catch (GeneralException $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Throwable $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function initialize_payment($data)
    {
            $user = auth()->user();
            $amount = $data['amount'];
            $metadata = ['amount' => $amount] + $data['metadata'] ?? [];

            $generate_payment_link = Payment::gateway($data['payment_gateway'])->setTransactionInitiator($user)
                ->initializeTransaction(
                    email: $user->email,
                    amount: $amount,
                    currency: $data['currency'],
                    callback: $data['callback_url'],
                    metadata: $metadata,
                );

            return  $generate_payment_link['authorization_url'] ?? $generate_payment_link['link'];
    }

    public function handlePaystackWebhook(VerifyPaystackRequest $request)
    {
        try {
            $this->handlePaystackWebhookService->handleWebhook($request->all());

            return response()->success('success');
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse('Something went wrong', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyTransaction($reference)
    {
        try {
            $data = $this->paymentGateway->verifyTransaction($reference);

            return response()->success('Transaction Retrieved', $data);
        } catch (GeneralException $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse('Something went wrong,', [], Response::HTTP_NOT_FOUND);
        }
    }

    public function show($id)
    {
        $transaction = PaymentLog::with('initiator', 'owner')->when(! request('is_admin'), function ($query) {
            $query->whereHasMorph('initiator', [User::class], function ($query) {
                $query->where('id', auth()->id())->where('status', TransactionStatusEnum::PAYMENT_SUCCESSFUL());
            });
        })->findOrFail($id);

        return response()->success('Payment retrieved', new TransactionResource($transaction));
    }

    public function all()
    {
        $transactions = PaymentLog::with('initiator', 'owner')->when(request('is_admin'), function ($q) {
            $user_id = request()->query('user_id');
            if ($user_id) {
                $q->whereHasMorph('initiator', [User::class], function ($q) use ($user_id) {
                    $q->where('id', $user_id);
                });
            }
        }, function ($q) {
            $q->whereHasMorph('initiator', [User::class], function ($q) {
                $q->where('id', auth()->id())->where('status', TransactionStatusEnum::PAYMENT_SUCCESSFUL());
            });
        })->latest()->paginate(config('constants.pagination_count'));

        return TransactionResource::collection($transactions)->additional(['status' => true, 'message' => 'Transactions retrieved successfully']);
    }

    public function getTransfers()
    {
        $user = auth()->user();

        $transfers = $user->transfers()->orderByDesc('created_at')->paginate(15);

        $totalTransferAmount = $user->transfers()->sum('amount');

        return response()->json([
            'status' => true,
            'message' => 'Transfers fetched successfully',
            'data' => TransferResource::collection($transfers),
            'total_transfer_amount' => number_format($totalTransferAmount, 2),
            'pagination' => [
                'total' => $transfers->total(),
                'per_page' => $transfers->perPage(),
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
            ],
        ]);
    }
}
