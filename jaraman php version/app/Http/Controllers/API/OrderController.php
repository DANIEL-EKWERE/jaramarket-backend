<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Exceptions\GeneralException;
use App\Http\Resources\OrderResource;
use App\Http\Requests\DecideOrderItemRequest;
use App\Http\Resources\IngredientOrderResource;

class OrderController extends Controller
{
    public function __construct(public OrderService $orderService) {}

    // ── Customer ──────────────────────────────────────────────────────

    public function all(Request $request)
    {
        try {
            $orders = $this->orderService->all((int) $request->get('per_page', 15));
            return response()->success('Orders retrieved successfully',
                OrderResource::collection($orders)->response()->getData(true), 200);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Order $order)
    {
        try {
            $order = $this->orderService->getOrderById($order->id);
            return response()->success('Order retrieved successfully', new OrderResource($order), 200);
        } catch (GeneralException $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(OrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder($request);
            return response()->success('Order created successfully', new OrderResource($order), 201);
        } catch (GeneralException $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancel(Order $order)
    {
        try {
            $order = $this->orderService->cancelOrder($order);
            return response()->success('Order cancelled successfully', new OrderResource($order), 200);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ── Vendor ────────────────────────────────────────────────────────

    /** GET /vendor/orders — available (pending) items for this vendor */
    public function getAvailableOrders(Request $request)
    {
        try {
            $orders = $this->orderService->getAvailableOrders((int) $request->get('per_page', 20));
            return response()->success('Available orders retrieved successfully',
                IngredientOrderResource::collection($orders)->response()->getData(true), 200);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** GET /vendor/orders/accepted — accepted orders assigned to this vendor */
    public function myOrders(Request $request)
    {
        try {
            $orders = $this->orderService->getMyOrders((int) $request->get('per_page', 20));
            return response()->success('Accepted orders retrieved successfully',
                IngredientOrderResource::collection($orders)->response()->getData(true), 200);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** GET /vendor/orders/{id} — single order item detail */
    public function showOrderByItemId(int $item_id)
    {
        try {
            $orderItem = $this->orderService->showOrderByItemId($item_id);
            return response()->success('Order retrieved successfully', new IngredientOrderResource($orderItem), 200);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** POST /vendor/orders/item/{id}/decision — accept or reject */
    public function decide(DecideOrderItemRequest $request, int $item_id)
    {
        try {
            $orderItem = $this->orderService->decide($request->validated(), $item_id);
            return response()->success('Action taken successfully', new IngredientOrderResource($orderItem), 200);
        } catch (Exception $e) {
            report($e);
            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
