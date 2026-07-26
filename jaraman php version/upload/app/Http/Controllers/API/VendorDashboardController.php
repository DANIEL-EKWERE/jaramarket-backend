<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Enums\StatusEnum;
use App\Enums\UserPermissionsEnum;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorDashboardController extends Controller
{
    /**
     * GET /api/vendor/dashboard
     *
     * Query params:
     *   period = day | week | month   (default: week)
     *
     * Response shape:
     * {
     *   period:          "week",
     *   date_range:      { from: "2026-03-10", to: "2026-03-17" },
     *   stats: {
     *     revenue:           1250.00,   // sum of vendor_amount on completed items
     *     orders_accepted:   18,        // distinct orders where vendor accepted ≥1 item
     *     orders_completed:  14,        // distinct orders fully completed for vendor
     *     orders_cancelled:  2,         // orders that were cancelled after vendor accepted
     *     orders_pending:    5,         // items still PENDING (not yet decided)
     *     total_items:       22,        // total order-items handled by vendor
     *     avg_order_value:   89.29,     // revenue / orders_completed (0 if none)
     *     commission_paid:   125.00,    // total commision deducted from vendor_amount
     *   },
     *   chart: [
     *     { date: "2026-03-11", revenue: 200.00, orders: 3 },
     *     ...
     *   ],
     *   recent_orders: [ ... ]          // last 5 completed order-items
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:day,week,month',
        ]);

        $vendor = auth()->user();

        /* ── Abort if caller is not a vendor (or admin viewing as vendor) ── */
        if (!in_array($vendor->role, [
            UserPermissionsEnum::VENDOR(),
            UserPermissionsEnum::ADMIN(),
        ])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        /* ── Date range ── */
        [$from, $to, $period] = $this->resolvePeriod($request->input('period', 'week'));

        /* ── Base scopes ── */

        // All order-items that belong to this vendor in the period
        $itemBase = OrderItem::where('vendor_id', $vendor->id)
            ->whereBetween('vendor_at', [$from, $to]);

        // Completed items (vendor earned money)
        $completedItems = (clone $itemBase)
            ->where('status', StatusEnum::COMPLETED());

        // Accepted = vendor said yes (status moved to PROCESSING or COMPLETED)
        $acceptedItems = (clone $itemBase)
            ->whereIn('status', [
                StatusEnum::PROCESSING(),
                StatusEnum::COMPLETED(),
            ]);

        /* ── Revenue ── */
        $revenue = (clone $completedItems)->sum('vendor_amount');

        /* ── Commission deducted ── */
        $commissionPaid = (clone $completedItems)->sum('commision');

        /* ── Distinct orders accepted ── */
        $ordersAccepted = (clone $acceptedItems)
            ->distinct('order_id')
            ->count('order_id');

        /* ── Distinct orders completed ── */
        $ordersCompleted = (clone $completedItems)
            ->distinct('order_id')
            ->count('order_id');

        /* ── Orders cancelled after vendor accepted ──
             An order was "cancelled" for this vendor if:
             - The parent order status = CANCELLED
             - AND vendor_id = this vendor (they had already accepted it)
        ── */
        $ordersCancelled = (clone $itemBase)
            ->whereHas('order', fn ($q) => $q->where('status', StatusEnum::CANCELLED()))
            ->distinct('order_id')
            ->count('order_id');

        /* ── Pending items (available but not yet decided) ── */
        $ordersPending = OrderItem::where('status', StatusEnum::PENDING())
            ->whereHas('ingredient.category', function ($q) use ($vendor) {
                $categoryIds = $vendor->categories()->pluck('category_id');
                $q->whereIn('id', $categoryIds);
            })
            ->whereBetween('created_at', [$from, $to])
            ->count();

        /* ── Total items handled ── */
        $totalItems = (clone $itemBase)->count();

        /* ── Average order value ── */
        $avgOrderValue = $ordersCompleted > 0
            ? round($revenue / $ordersCompleted, 2)
            : 0.00;

        /* ── Chart data — daily breakdown within the period ── */
        $chart = $this->buildChart($vendor->id, $from, $to, $period);

        /* ── Recent completed order-items (last 5) ── */
        $recentOrders = OrderItem::with(['order:id,reference,total,status,created_at', 'ingredient:id,name'])
            ->where('vendor_id', $vendor->id)
            ->where('status', StatusEnum::COMPLETED())
            ->orderByDesc('vendor_at')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'item_id'        => $item->id,
                'order_ref'      => $item->order?->reference,
                'ingredient'     => $item->ingredient?->name,
                'quantity'       => $item->quantity,
                'vendor_amount'  => (float) $item->vendor_amount,
                'completed_at'   => $item->vendor_at?->toDateTimeString(),
            ]);

        return response()->json([
            'period'     => $period,
            'date_range' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
            'stats' => [
                'revenue'          => round((float) $revenue, 2),
                'orders_accepted'  => $ordersAccepted,
                'orders_completed' => $ordersCompleted,
                'orders_cancelled' => $ordersCancelled,
                'orders_pending'   => $ordersPending,
                'total_items'      => $totalItems,
                'avg_order_value'  => $avgOrderValue,
                'commission_paid'  => round((float) $commissionPaid, 2),
            ],
            'chart'         => $chart,
            'recent_orders' => $recentOrders,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
     |  HELPERS
     ───────────────────────────────────────────────────────────── */

    /**
     * Resolve the Carbon date range from the period string.
     *
     * @return array{Carbon, Carbon, string}
     */
    private function resolvePeriod(string $period): array
    {
        $now = Carbon::now();

        $from = match ($period) {
            'day'   => $now->copy()->startOfDay(),
            'month' => $now->copy()->startOfMonth(),
            default => $now->copy()->startOfWeek(),   // 'week'
        };

        return [$from, $now->copy()->endOfDay(), $period];
    }

    /**
     * Build per-day chart data for the given period.
     *
     * Returns an array of:
     *   [ { date, revenue, orders_completed, orders_accepted } ]
     */
    private function buildChart(int $vendorId, Carbon $from, Carbon $to, string $period): array
    {
        /* Aggregate raw data from DB — one row per day */
        $rows = OrderItem::selectRaw(
                'DATE(vendor_at) as day,
                 SUM(CASE WHEN status = ? THEN vendor_amount ELSE 0 END) as revenue,
                 COUNT(DISTINCT CASE WHEN status = ? THEN order_id END) as orders_completed,
                 COUNT(DISTINCT CASE WHEN status IN (?,?) THEN order_id END) as orders_accepted',
                [
                    StatusEnum::COMPLETED(),
                    StatusEnum::COMPLETED(),
                    StatusEnum::PROCESSING(),
                    StatusEnum::COMPLETED(),
                ]
            )
            ->where('vendor_id', $vendorId)
            ->whereBetween('vendor_at', [$from, $to])
            ->groupByRaw('DATE(vendor_at)')
            ->orderBy('day')
            ->get()
            ->keyBy('day');   // index by date string for easy lookup

        /* Fill every calendar day in range (including days with 0 activity) */
        $chart = [];
        $step  = $period === 'day' ? 'hour' : 'day';

        if ($period === 'day') {
            // Hourly breakdown for "today"
            for ($h = 0; $h < 24; $h++) {
                $label = $from->copy()->addHours($h)->format('H:00');
                $chart[] = [
                    'date'             => $label,
                    'revenue'          => 0.00,
                    'orders_completed' => 0,
                    'orders_accepted'  => 0,
                ];
            }
            // Fill in actuals
            $hourlyRaw = OrderItem::selectRaw(
                    'HOUR(vendor_at) as hr,
                     SUM(CASE WHEN status = ? THEN vendor_amount ELSE 0 END) as revenue,
                     COUNT(DISTINCT CASE WHEN status = ? THEN order_id END) as orders_completed,
                     COUNT(DISTINCT CASE WHEN status IN (?,?) THEN order_id END) as orders_accepted',
                    [
                        StatusEnum::COMPLETED(),
                        StatusEnum::COMPLETED(),
                        StatusEnum::PROCESSING(),
                        StatusEnum::COMPLETED(),
                    ]
                )
                ->where('vendor_id', $vendorId)
                ->whereBetween('vendor_at', [$from, $to])
                ->groupByRaw('HOUR(vendor_at)')
                ->get()
                ->keyBy('hr');

            foreach ($chart as $i => &$slot) {
                $row = $hourlyRaw->get($i);
                if ($row) {
                    $slot['revenue']          = round((float) $row->revenue, 2);
                    $slot['orders_completed'] = (int) $row->orders_completed;
                    $slot['orders_accepted']  = (int) $row->orders_accepted;
                }
            }
        } else {
            // Daily breakdown for week / month
            $period_obj = CarbonPeriod::create($from->toDateString(), '1 day', $to->toDateString());

            foreach ($period_obj as $date) {
                $key = $date->toDateString();
                $row = $rows->get($key);
                $chart[] = [
                    'date'             => $key,
                    'revenue'          => $row ? round((float) $row->revenue, 2) : 0.00,
                    'orders_completed' => $row ? (int) $row->orders_completed : 0,
                    'orders_accepted'  => $row ? (int) $row->orders_accepted  : 0,
                ];
            }
        }

        return $chart;
    }
}
