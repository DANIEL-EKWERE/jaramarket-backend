<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(title="JaraMarket API", version="1.0")
 * @OA\Server(url="http://localhost:8000")
 * @OA\PathItem(
 *     path="/orders",
 *     description="Operations related to orders"
 * )
 */
class CartController extends Controller
{
    /**
     * Display a listing of the user's carts.
     */
    public function index()
    {
        $user = Auth::user();
        $carts = Cart::where('user_id', $user->id)
            ->with('items.product')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $carts
        ]);
    }

    /**
     * Store a newly created cart.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();
        
        // Get or create cart for the user
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['total_amount' => 0]
        );

        $product = Product::findOrFail($request->product_id);
        
        // Check if product already in cart
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $request->quantity,
                'price' => $product->price * ($existingItem->quantity + $request->quantity == 0 ? 1 : $existingItem->quantity + $request->quantity)
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'price' => $product->price * ($request->quantity == 0 ? 1 : $request->quantity)
            ]);
        }

        // Update cart total
        $this->updateCartTotal($cart);

        return response()->json([
            'status' => 'success',
            'message' => 'Product added to cart successfully',
            'data' => $cart->load('items.product')
        ], 201);
    }

    /**
     * Display the specified cart.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)
            ->with('items.product')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $cart
        ]);
    }

    /**
     * Update the specified cart item.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)
            ->findOrFail($id);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $request->item_id)
            ->firstOrFail();

        $product = Product::findOrFail($cartItem->product_id);
        
        $cartItem->update([
            'quantity' => $request->quantity,
            'price' => $product->price * $request->quantity == 0 ? 1 : $product->price * $request->quantity
        ]);

        // Update cart total
        $this->updateCartTotal($cart);

        return response()->json([
            'status' => 'success',
            'message' => 'Cart item updated successfully',
            'data' => $cart->load('items.product')
        ]);
    }

    /**
     * Remove the specified cart item.
     */
    public function destroy(Request $request, string $id)
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)
            ->findOrFail($id);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $request->item_id)
            ->firstOrFail();

        $cartItem->delete();

        // Update cart total
        $this->updateCartTotal($cart);

        return response()->json([
            'status' => 'success',
            'message' => 'Cart item removed successfully'
        ]);
    }

    /**
     * Helper method to update cart total
     */
    private function updateCartTotal($cart)
    {
        $total = CartItem::where('cart_id', $cart->id)
            ->sum('price');

        $cart->update(['total_amount' => $total]);
    }
}
