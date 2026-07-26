<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\Wallet;
use Carbon\Carbon;
use App\Enums\StatusEnum;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function orders(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $orders = Order::whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->with('user')
            ->latest()
            ->paginate(25);

        $totalOrders = $orders->total();
        $totalRevenue = $orders->sum('total');
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Previous period stats
        $previousPeriodStart = now()->subDays(60)->format('Y-m-d');
        $previousPeriodEnd = now()->subDays(31)->format('Y-m-d');

        $previousOrders = Order::whereBetween('created_at', [$previousPeriodStart.' 00:00:00', $previousPeriodEnd.' 23:59:59'])->count();
        $previousRevenue = Order::whereBetween('created_at', [$previousPeriodStart.' 00:00:00', $previousPeriodEnd.' 23:59:59'])->sum('total');
        $previousAOV = $previousOrders > 0 ? $previousRevenue / $previousOrders : 0;

        $orderGrowth = $previousOrders > 0 ? (($totalOrders - $previousOrders) / $previousOrders) * 100 : 0;
        $revenueGrowth = $previousRevenue > 0 ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
        $aovGrowth = $previousAOV > 0 ? (($averageOrderValue - $previousAOV) / $previousAOV) * 100 : 0;

        $ordersByDate = Order::whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $orderChartLabels = $ordersByDate->pluck('date')->toArray();
        $orderChartData = $ordersByDate->pluck('count')->toArray();
        $revenueChartData = $ordersByDate->pluck('revenue')->toArray();

        $revenueByStatus = Order::whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->selectRaw('status, SUM(total) as total')
            ->groupBy('status')
            ->get();

        $statusChartLabels = $revenueByStatus->pluck('status')->toArray();
        $statusChartData = $revenueByStatus->pluck('total')->toArray();

        $conversionRate = 5; // example
        $conversionGrowth = 0;

        $ordersByStatus = $orders->groupBy('status')->map(fn($statusOrders) => $statusOrders->count());

        $customers = User::whereHas('orders')->get();

        return view('reports.orders', compact(
            'orders','totalOrders','totalRevenue','averageOrderValue',
            'conversionRate','orderGrowth','revenueGrowth','aovGrowth',
            'conversionGrowth','ordersByStatus','startDate','endDate',
            'customers','orderChartLabels','orderChartData','revenueChartData',
            'statusChartLabels','statusChartData'
        ));
    }

    public function products()
    {
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_quantity'), DB::raw('SUM(order_items.price * order_items.quantity) as total_sales'))
            ->groupBy('products.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        $lowStockProducts = Product::where('stock', '<', 10)->get();

        return view('reports.products', compact('topProducts', 'lowStockProducts'));
    }

    public function exportOrders(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $orders = Order::whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->with('user')
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="orders-report.csv"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order ID', 'Customer', 'Total', 'Status', 'Date']);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->user->name,
                    $order->total,
                    $order->status,
                    $order->created_at->format('Y-m-d H:i:s')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    public function index()
    {
        return view('reports.summary');
    }

    public function getSummary(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfDay());
        $endDate   = $request->input('end_date', Carbon::today()->endOfDay());

        $startDate = Carbon::parse($startDate)->startOfDay(); // ensures 00:00:00
        $endDate   = Carbon::parse($endDate)->endOfDay();     // ensures 23:59:59

        $order = Order::query()
        ->selectRaw('
            SUM(shipping_fee) as delivery_charge,
            SUM(service_charge) as service_charge,
            SUM(vat) as vat,
            SUM(total) as total_order
        ')
        ->whereNot('status', StatusEnum::CANCELLED())
        ->whereBetween('created_at', [$startDate, $endDate])
        ->first();

        $order_details = OrderItem::query()
        ->selectRaw('
            SUM(commision) as total_commission,
            SUM(referral) as total_referral_bonus,
            SUM(vendor_amount) as total_vendor_amount
        ')
        ->whereNot('status', StatusEnum::CANCELLED())
        ->whereBetween('created_at', [$startDate, $endDate])
        ->first();

        $transfer = Transfer::WhereIn('status', [StatusEnum::SUCCESS(), StatusEnum::PENDING()])->whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        $summary = [
            'total_orders_amount' => number_format($order->total_order,2) ?? 0,
            'delivery_charge' => number_format($order->delivery_charge,2) ?? 0,
            'service_charge' => number_format($order->service_charge,2) ?? 0,
            'vat' => number_format($order->vat,2) ?? 0,
            'total_commission' => number_format($order_details->total_commission,2) ?? 0,
            'total_referral_bonus' => number_format($order_details->total_referral_bonus,2) ?? 0,
            'vendor_amount' => number_format($order_details->total_vendor_amount,2) ?? 0,
            'wallet_balance' => number_format(Wallet::sum('balance'),2),
            'total_transfers' => number_format($transfer,2),
            'total_deposits' => number_format(PaymentLog::where('status', StatusEnum::SUCCESS())->whereBetween('created_at', [$startDate, $endDate])->sum('amount'),2),
        ];
        return response()->json([
            'status' => true,
            'message' => 'Summary fetched successfully',
            'data' => $summary,
            'date_range' => [
                'start' => Carbon::parse($startDate)->toDateTimeString(),
                'end'   => Carbon::parse($endDate)->toDateTimeString(),
            ]
        ]);
    }

}
