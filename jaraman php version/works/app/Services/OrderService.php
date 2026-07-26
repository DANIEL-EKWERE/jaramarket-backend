<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\Firebase\FirebaseNotificationService;
use App\Enums\WalletTransactionTypeEnum;
use App\Enums\OrderNotificationTypeEnum;
use App\Enums\UserPermissionsEnum;
use App\Enums\StatusEnum;
use App\Models\User;
use App\Models\OrderItemLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Ingredient;
use App\Notifications\OrderStatusNotification;
use App\Notifications\WalletNotification;
use App\Utils\Util;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        public TransactionLogService $transactionLogService,
        protected FirebaseNotificationService $firebase
    ) {}

    public function all(int $perPage = 15)
    {
        return Order::with(['items.product','items.ingredient','address'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate($perPage);
    }

    public function getOrderById(int $id): Order
    {
        return Order::with(['items.product','items.ingredient','address'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);
    }

    public function createOrder($request): Order
    {
        return DB::transaction(function () use ($request) {
            $data   = $request->validated();
            $user   = auth()->user();
            $wallet = $user->wallet;

            if (!$wallet || $wallet->balance < $data['total']) {
                throw new Exception('Insufficient wallet balance.');
            }

            $audio_url = null;
            if ($request->hasFile('audio')) {
                $audio_url = upload_image('orders/audio', $request->audio);
            }

            $order = Order::create([
                'order_date'     => $data['order_date'],
                'reference'      => Util::generate_order_txn_ref(),
                'user_id'        => $user->id,
                'address_id'     => $data['address_id'] ?? null,
                'delivery_type'  => $data['delivery_type'],
                'shipping_fee'   => $data['shipping_fee'] ?? 0,
                'service_charge' => $data['service_charge'],
                'vat'            => $data['vat'] ?? 0,
                'total'          => $data['total'],
                'status'         => StatusEnum::PENDING(),
                'audio'          => $audio_url,
                'remarks'        => $data['remarks'],
            ]);

            $this->transactionLogService::debit(
                $user->id, get_class($user), $data['total'],
                $order->id, get_class($order), 'NGN', "Payment for Order #{$order->reference}"
            );

            $this->saveFood($data, $order, $user);
            $this->saveIngredients($data, $order, $user);
            $order->load(['items.product','items.ingredient','address']);

            // Firebase notifications (replaces Kafka)
            $this->firebase->sendToUser($user, 'Order Placed', "Your order #{$order->reference} has been placed.", [
                'type' => 'order_created', 'order_id' => (string)$order->id, 'status' => $order->status,
            ]);

            $user->notify(new WalletNotification(
                WalletTransactionTypeEnum::DEBIT(),
                (float) $data['total'],
                (float) $user->wallet->fresh()->balance,
                $order->reference,
                "Payment for Order #{$order->reference}"
            ));

            $this->notifyVendorsOfNewOrder($order);
            return $order;
        });
    }

    public function cancelOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            if ($order->status !== StatusEnum::PENDING()) {
                throw new Exception('You cannot cancel this order.');
            }
            $user = auth()->user();
            $this->transactionLogService::credit(
                $user->id, get_class($user), $order->total,
                $order->id, get_class($order), 'NGN', "Refund from Order #{$order->reference}"
            );
            $order->update(['status' => StatusEnum::CANCELLED()]);

            $this->firebase->sendToUser($user, 'Order Cancelled', "Your order #{$order->reference} has been cancelled and refunded.", [
                'type' => 'order_cancelled', 'order_id' => (string)$order->id,
            ]);

            $user->notify(new WalletNotification(
                WalletTransactionTypeEnum::CREDIT(),
                (float) $order->total,
                (float) $user->wallet->fresh()->balance,
                $order->reference,
                "Refund from Order #{$order->reference}"
            ));
            return $order;
        });
    }

    public function getAvailableOrders(int $perPage = 20)
    {
        $user  = auth()->user();
        $query = OrderItem::with(['ingredient.category','order.user','order.address'])
            ->where('status', StatusEnum::PENDING());

        if ($user->role !== UserPermissionsEnum::ADMIN()) {
            $categoryIds = $user->categories()->pluck('category_id')->toArray();
            $query->whereHas('ingredient', fn ($q) => $q->whereIn('category_id', $categoryIds));
        }
        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function showOrderByItemId(int $itemId): OrderItem
    {
        return OrderItem::with(['ingredient.category','order.user','order.address','vendor'])
            ->findOrFail($itemId);
    }

    public function getMyOrders(int $perPage = 20)
    {
        $user  = auth()->user();
        $query = OrderItem::with(['order.user','order.address','ingredient','vendor']);

        if ($user->role === UserPermissionsEnum::ADMIN()) {
            $query->whereNotNull('vendor_id');
        } else {
            $query->where('vendor_id', $user->id);
        }
        return $query->orderByDesc('vendor_at')->paginate($perPage);
    }

    public function decide(array $data, int $itemId): OrderItem
    {
        $user = auth()->user();
        return DB::transaction(function () use ($user, $data, $itemId) {
            $status = $data['status'] === StatusEnum::ACCEPTED()
                ? StatusEnum::PROCESSING()
                : StatusEnum::PENDING();

            $orderItem = OrderItem::with('order.items','order.user')->findOrFail($itemId);
            $vendor_id = $user->id;
            if ($user->role === UserPermissionsEnum::ADMIN() && isset($data['vendor_id'])) {
                $vendor_id = $data['vendor_id'];
            }

            $orderItem->update(['status' => $status, 'vendor_id' => $vendor_id, 'vendor_at' => now()]);

            OrderItemLog::create([
                'order_item_id' => $orderItem->id,
                'vendor_id'     => $vendor_id,
                'status'        => $status,
                'changed_at'    => now(),
            ]);

            $order = $orderItem->order;
            $pendingCount = $order->items()->where('status', '!=', StatusEnum::PROCESSING())->count();

            if ($pendingCount === 0) {
                $order->update(['status' => StatusEnum::PROCESSING()]);
                $order->user->notify(new OrderStatusNotification($order, OrderNotificationTypeEnum::CUSTOMER(), StatusEnum::PROCESSING()));
                $this->firebase->sendToUser($order->user, 'Order Processing', "Your order #{$order->reference} is now being processed.", [
                    'type' => 'order_processing', 'order_id' => (string)$order->id,
                ]);
                $vendors = $order->items->pluck('vendor')->unique()->filter();
                foreach ($vendors as $vendor) {
                    $vendor->notify(new OrderStatusNotification($order, OrderNotificationTypeEnum::VENDOR(), StatusEnum::PROCESSING()));
                }
            }
            return $orderItem->fresh(['order','ingredient','vendor']);
        });
    }

    public function markAsCompleted(int $id): Order
    {
        return DB::transaction(function () use ($id) {
            $order = Order::with('items')->findOrFail($id);
            $user  = auth()->user();

            if (in_array($order->status, [StatusEnum::COMPLETED(), StatusEnum::CANCELLED()])) {
                throw new Exception("Order #{$order->reference} cannot be marked as completed again.");
            }

            $order->update(['status' => StatusEnum::COMPLETED()]);
            $order->items()->update([
                'status'                 => StatusEnum::COMPLETED(),
                'assurance_user_id'      => $user->id,
                'assurance_at'           => now(),
                'pass_quality_assurance' => true,
            ]);

            $order->user->notify(new OrderStatusNotification($order, OrderNotificationTypeEnum::CUSTOMER(), StatusEnum::COMPLETED()));
            $this->firebase->sendToUser($order->user, 'Order Completed', "Your order #{$order->reference} has been completed!", [
                'type' => 'order_completed', 'order_id' => (string)$order->id,
            ]);

            $vendorCredits = $order->items->whereNotNull('vendor_id')
                ->groupBy('vendor_id')->map(fn ($items) => $items->sum('vendor_amount'));

            foreach ($vendorCredits as $vendorId => $amount) {
                if ($amount <= 0) continue;
                $vendor = User::find($vendorId);
                if (!$vendor) continue;
                $this->transactionLogService::credit(
                    $vendorId, get_class($vendor), $amount, $order->id, get_class($order),
                    'NGN', "Payment from Order #{$order->reference}"
                );
                $vendor->notify(new WalletNotification(
                    WalletTransactionTypeEnum::CREDIT(),
                    (float) $amount,
                    (float) $vendor->wallet->fresh()->balance,
                    $order->reference,
                    "Payment from Order #{$order->reference}"
                ));
                $this->firebase->sendToUser($vendor, 'Payment Received', "You received ₦".number_format($amount,2)." for order #{$order->reference}.", [
                    'type' => 'vendor_credited', 'order_id' => (string)$order->id,
                ]);
            }

            $referralCredits = $order->items->whereNotNull('referral_id')
                ->groupBy('referral_id')->map(fn ($items) => $items->sum('referral'));

            foreach ($referralCredits as $referralId => $amount) {
                if ($amount <= 0) continue;
                $referral = User::find($referralId);
                if (!$referral) continue;
                $this->transactionLogService::credit(
                    $referralId, get_class($referral), $amount, $order->id, get_class($order), 'NGN', 'Referral commission'
                );
            }
            return $order;
        });
    }

    private function getBonuses(float $price, int $quantity, Order $order, User $user): array
    {
        $settings   = Setting::whereIn('key', ['first_order_bonus','repeat_order_bonus'])->pluck('value','key');
        $item_total = $price * $quantity;
        $result     = Util::getCommission($item_total, $order->total);
        $commission = $result['commission'];
        $referral_commission = 0;
        $referral_id = null;
        if ($user->referrer_id) {
            $previousOrders  = Order::where('user_id', $user->id)->count();
            $isFirstShopping = $previousOrders === 0;
            $percentage      = $isFirstShopping ? ($settings['first_order_bonus'] ?? 0) : ($settings['repeat_order_bonus'] ?? 0);
            $referral_commission = ($commission * $percentage) / 100;
            $referral_id = $user->referrer_id;
        }
        return ['referral_commission' => $referral_commission, 'referral_id' => $referral_id, 'commission' => $commission, 'item_total' => $item_total];
    }

    private function saveIngredients(array $data, Order $order, User $user): void
    {
        foreach ($data['ingredients'] ?? [] as $ingredient) {
            $model = Ingredient::findOrFail($ingredient['ingredient_id']);
            $price = $ingredient['price'] ?? $model->price;
            $bonus = $this->getBonuses($price, $ingredient['quantity'], $order, $user);
            $order->items()->create([
                'ingredient_id' => $model->id, 'quantity' => $ingredient['quantity'],
                'price' => $price, 'unit' => $ingredient['unit'], 'amount' => $bonus['item_total'],
                'commision' => $bonus['commission'], 'vendor_amount' => $bonus['item_total'] - $bonus['commission'],
                'referral' => $bonus['referral_commission'], 'referral_id' => $bonus['referral_id'],
            ]);
        }
    }

    private function saveFood(array $data, Order $order, User $user): void
    {
        foreach ($data['products'] ?? [] as $productData) {
            $product = Product::with('ingredients')->findOrFail($productData['product_id']);
            foreach ($product->ingredients as $ingredient) {
                $quantity = $ingredient->pivot->quantity ?? 1;
                $bonus    = $this->getBonuses($ingredient->price, $quantity, $order, $user);
                $order->items()->create([
                    'product_id' => $product->id, 'ingredient_id' => $ingredient->id,
                    'quantity' => $quantity, 'price' => $ingredient->price, 'unit' => $ingredient->pivot->unit ?? null,
                    'amount' => $bonus['item_total'], 'commision' => $bonus['commission'],
                    'vendor_amount' => $bonus['item_total'] - $bonus['commission'],
                    'referral' => $bonus['referral_commission'], 'referral_id' => $bonus['referral_id'],
                ]);
            }
        }
    }

    private function notifyVendorsOfNewOrder(Order $order): void
    {
        $categoryIds = $order->items()->with('ingredient:id,category_id')
            ->get()->pluck('ingredient.category_id')->unique()->filter();

        $vendors = User::where('role', UserPermissionsEnum::VENDOR())
            ->whereHas('categories', fn ($q) => $q->whereIn('category_id', $categoryIds))->get();

        $this->firebase->sendToUsers($vendors, 'New Order Available', 'A new order matching your categories is available.', [
            'type' => 'new_order_available', 'order_id' => (string)$order->id,
        ]);
    }
}
