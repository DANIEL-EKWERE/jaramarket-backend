<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusEnum;
use App\Enums\UserPermissionsEnum;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VendorManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin', 'permission:view_vendors']);
    }

    public function index()
    {
        $states     = State::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        return view('admin.vendors.index', compact('states', 'categories'));
    }

    public function getData(Request $request)
    {
        $query = User::vendors()
            ->with(['state', 'categories', 'wallet'])
            ->when($request->state_id,    fn ($q) => $q->where('state_id', $request->state_id))
            ->when($request->category_id, fn ($q) => $q->whereHas('categories', fn ($q2) =>
                $q2->where('categories.id', $request->category_id)))
            ->when($request->status, fn ($q) => $q->where('is_active', $request->status === 'active'));

        return DataTables::of($query)
            ->addColumn('vendor_name', fn ($v) => $v->name)
            ->addColumn('email',       fn ($v) => $v->email)
            ->addColumn('phone',       fn ($v) => $v->phone_number ?? '—')
            ->addColumn('state',       fn ($v) => $v->state?->name ?? '—')
            ->addColumn('categories',  fn ($v) => $v->categories->pluck('name')->implode(', ') ?: '—')
            ->addColumn('wallet_balance', fn ($v) => auth()->user()->hasPermission('view_wallets')
                ? '₦'.number_format($v->wallet?->balance ?? 0, 2) : '—')
            ->addColumn('status_badge', fn ($v) =>
                $v->is_active
                    ? '<span class="badge-success">Active</span>'
                    : '<span class="badge-danger">Inactive</span>')
            ->addColumn('actions', fn ($v) =>
                '<div class="flex gap-1">
                    <a href="'.route('admin.vendors.show', $v).'" class="btn-xs-primary">View</a>
                    <a href="'.route('admin.vendors.orders', $v).'" class="btn-xs-secondary">Orders</a>
                </div>')
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function show(User $vendor)
    {
        abort_unless($vendor->isVendor(), 404);
        $vendor->load(['categories', 'state', 'wallet', 'bankAccounts']);

        $stats = [
            'total_orders'     => OrderItem::where('vendor_id', $vendor->id)->count(),
            'pending_orders'   => OrderItem::where('vendor_id', $vendor->id)->where('status', StatusEnum::PENDING())->count(),
            'accepted_orders'  => OrderItem::where('vendor_id', $vendor->id)->where('status', StatusEnum::PROCESSING())->count(),
            'completed_orders' => OrderItem::where('vendor_id', $vendor->id)->where('status', StatusEnum::COMPLETED())->count(),
            'total_earned'     => OrderItem::where('vendor_id', $vendor->id)->where('status', StatusEnum::COMPLETED())->sum('vendor_amount'),
        ];

        $recentOrders = OrderItem::where('vendor_id', $vendor->id)
            ->with(['order.user', 'ingredient'])->latest()->take(10)->get();

        return view('admin.vendors.show', compact('vendor', 'stats', 'recentOrders'));
    }

    public function vendorOrders(Request $request, User $vendor)
    {
        abort_unless($vendor->isVendor(), 404);
        $status   = $request->get('status');
        $statuses = StatusEnum::values();
        $orders   = OrderItem::where('vendor_id', $vendor->id)
            ->with(['order.user', 'order.address', 'ingredient.category'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.vendors.orders', compact('vendor', 'orders', 'statuses', 'status'));
    }

    public function toggleStatus(User $vendor)
    {
        abort_unless($vendor->isVendor(), 404);
        if (!auth()->user()->hasPermission('manage_vendors')) abort(403);
        $vendor->update(['is_active' => !$vendor->is_active]);
        return back()->with('success', 'Vendor status updated.');
    }

    public function toggleVerification(User $vendor)
    {
        abort_unless($vendor->isVendor(), 404);
        if (!auth()->user()->hasPermission('manage_vendors')) abort(403);
        $vendor->update(['is_verified' => !($vendor->is_verified ?? false)]);
        return back()->with('success', 'Vendor verification updated.');
    }
}
