<?php

namespace App\Http\Controllers;

use App\Enums\StatusEnum;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $stateId = $user->isStateAdmin() ? $user->state_id : null;
        $stats   = [];

        if ($user->hasPermission('view_orders')) {
            $oq = Order::when($stateId, fn ($q) =>
                $q->whereHas('user', fn ($u) => $u->where('state_id', $stateId)));
            $stats['total_orders']      = $oq->count();
            $stats['pending_orders']    = (clone $oq)->where('status', StatusEnum::PENDING())->count();
            $stats['processing_orders'] = (clone $oq)->where('status', StatusEnum::PROCESSING())->count();
            $stats['completed_orders']  = (clone $oq)->where('status', StatusEnum::COMPLETED())->count();
            $stats['cancelled_orders']  = (clone $oq)->where('status', StatusEnum::CANCELLED())->count();
        }

        if ($user->hasPermission('view_transactions')) {
            $stats['total_revenue'] = Order::where('status', StatusEnum::COMPLETED())->sum('total');
            $stats['today_revenue'] = Order::where('status', StatusEnum::COMPLETED())->whereDate('created_at', today())->sum('total');
        }

        if ($user->hasPermission('view_users')) {
            $stats['total_customers'] = User::customers()->when($stateId, fn ($q) => $q->where('state_id', $stateId))->count();
        }

        if ($user->hasPermission('view_vendors')) {
            $stats['total_vendors'] = User::vendors()->when($stateId, fn ($q) => $q->where('state_id', $stateId))->count();
        }

        $recentOrders = $user->hasPermission('view_orders')
            ? Order::with('user')
                ->when($stateId, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('state_id', $stateId)))
                ->latest()->take(8)->get()
            : collect();

        $latestUsers = $user->hasPermission('view_users')
            ? User::customers()->when($stateId, fn ($q) => $q->where('state_id', $stateId))->latest()->take(6)->get()
            : collect();

        $productsChartData  = ['labels' => [], 'data' => []];
        $orderStatusChart   = [];

        if ($user->hasPermission('view_reports')) {
            $topProducts = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select('products.name', DB::raw('SUM(order_items.quantity) as total_quantity'))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_quantity')->limit(6)->get();

            $productsChartData = [
                'labels' => $topProducts->pluck('name')->toArray(),
                'data'   => $topProducts->pluck('total_quantity')->map(fn ($v) => (int)$v)->toArray(),
            ];
        }

        if ($user->hasPermission('view_orders')) {
            $orderStatusChart = Order::selectRaw('status, COUNT(*) as count')
                ->when($stateId, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('state_id', $stateId)))
                ->groupBy('status')->pluck('count', 'status')->toArray();
        }

        return view('dashboard', compact('stats', 'recentOrders', 'latestUsers', 'productsChartData', 'orderStatusChart'));
    }
}
