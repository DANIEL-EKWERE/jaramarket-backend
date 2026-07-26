<?php

namespace App\Http\Controllers;

use Exception;
use App\Services\OrderService;
use App\Enums\StatusEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    public function __construct(public OrderService $orderService)
    { }

    public function index()
    {
        return view('orders.index');
    }

    public function getData(Request $request)
    {
        $query = Order::with('user')
            ->when($request->status, function ($q) use ($request) {
                // Filter by selected status
                $q->where('status', $request->status);
            }, function ($q) {
                // No status filter → show today's orders
                $q->whereDate('created_at', now()->toDateString());
            })
            ->when($request->search, function ($q) use ($request) {
                // Search across reference, status, and customer name
                $term = $request->search;
                $q->where(function ($q2) use ($term) {
                    $q2->where('reference', 'like', "%{$term}%")
                       ->orWhere('status', 'like', "%{$term}%")
                       ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$term}%"));
                });
            });

        return DataTables::of($query)
            ->addColumn('customer', fn($order) => $order->user->name ?? 'N/A')

            // FIX 1: Add meal_prep so column 5 in the blade is populated
            ->addColumn('meal_prep', fn($order) => $order->meal_prep ?? null)

            // FIX 2: Keep raw_status and raw_total BEFORE editColumn transforms them
            //         so the stat cards (loadStats) can read the original values.
            ->addColumn('raw_status', fn($order) => $order->getRawOriginal('status') ?? $order->status)
            ->addColumn('raw_total',  fn($order) => $order->getRawOriginal('total')  ?? $order->total)

            // Display-formatted columns (these replace the visible values in the table)
            ->editColumn('total', fn($order) => number_format($order->total, 2))
            ->editColumn('status', fn($order) => $order->status) // raw string — blade JS handles badge styling
            ->editColumn('created_at', fn($order) => $order->created_at->format('d M Y H:i'))

            ->addColumn('actions', fn($order) =>
                '<a href="' . route('orders.show', $order) . '"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium
                           text-slate-600 bg-white border border-slate-200 rounded-lg
                           hover:bg-slate-50 hover:border-slate-300 transition-colors">
                    View
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>'
            )
            ->rawColumns(['actions'])
            ->make(true);
    }

    // Show create order form
    public function create()
    {
        $products = Product::all();
        $users    = User::all();
        return view('orders.create', compact('products', 'users'));
    }

    // Store new order
    public function store(Request $request)
    {
        // Implement validation & create logic
    }

    // Show order details
    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'items.vendor']);
        return view('orders.show', compact('order'));
    }

    // Update order status
    public function updateStatus(Request $request, Order $order)
    {
        try {
            $this->orderService->markAsCompleted($order->id);
            return redirect()->back()->with('success', 'Order completed successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to complete order: ' . $e->getMessage());
        }
    }

    // Cancel order
    public function destroy(Order $order)
    {
        try {
            $order->update(['status' => StatusEnum::CANCELLED()]);
            return redirect()->back()->with('success', 'Order cancelled successfully');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to cancel order: ' . $e->getMessage());
        }
    }
}
