<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransactionLog;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class FinanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    // ── Transactions ──────────────────────────────────────────────────

    public function transactions(Request $request)
    {
        $this->requireAny(['view_transactions']);
        return view('admin.finance.transactions');
    }

    public function getTransactionsData(Request $request)
    {
        $this->requireAny(['view_transactions']);
        $query = TransactionLog::with(['account_owner'])
            ->when($request->type,    fn ($q) => $q->where('transaction_type', $request->type))
            ->when($request->start,   fn ($q) => $q->whereDate('created_at', '>=', $request->start))
            ->when($request->end,     fn ($q) => $q->whereDate('created_at', '<=', $request->end))
            ->latest();

        return DataTables::of($query)
            ->addColumn('user_name',            fn ($t) => $t->account_owner?->name ?? '—')
            ->addColumn('user_email',           fn ($t) => $t->account_owner?->email ?? '—')
            ->addColumn('formatted_amount',     fn ($t) => '₦'.number_format($t->amount, 2))
            ->addColumn('formatted_old_balance',fn ($t) => '₦'.number_format($t->old_balance / 100, 2))
            ->addColumn('formatted_new_balance',fn ($t) => '₦'.number_format($t->new_balance / 100, 2))
            ->addColumn('type_badge', fn ($t) =>
                $t->transaction_type === 'credit'
                    ? '<span class="badge-success">Credit</span>'
                    : '<span class="badge-danger">Debit</span>')
            ->addColumn('date', fn ($t) => $t->created_at->format('d M Y H:i'))
            ->rawColumns(['type_badge'])
            ->make(true);
    }

    // ── Wallets ───────────────────────────────────────────────────────

    public function wallets()
    {
        $this->requireAny(['view_wallets']);
        $summary = [
            'total_user_balance'   => Wallet::whereHas('user', fn ($q) => $q->where('role', 'customer'))->sum('balance'),
            'total_vendor_balance' => Wallet::whereHas('user', fn ($q) => $q->where('role', 'vendor'))->sum('balance'),
            'total_wallets'        => Wallet::count(),
        ];
        return view('admin.finance.wallets', compact('summary'));
    }

    public function getWalletsData(Request $request)
    {
        $this->requireAny(['view_wallets']);
        $query = Wallet::with(['user'])
            ->when($request->role,        fn ($q) => $q->whereHas('user', fn ($q2) => $q2->where('role', $request->role)))
            ->when($request->min_balance, fn ($q) => $q->where('balance', '>=', $request->min_balance * 100));

        return DataTables::of($query)
            ->addColumn('user_name',        fn ($w) => $w->user?->name ?? '—')
            ->addColumn('user_email',       fn ($w) => $w->user?->email ?? '—')
            ->addColumn('user_role',        fn ($w) => $w->user?->role ?? '—')
            ->addColumn('formatted_balance',fn ($w) => '₦'.number_format($w->balance, 2))
            ->addColumn('actions', fn ($w) =>
                '<a href="'.route('admin.finance.user-transactions', $w->user_id).'" class="btn-xs-primary">Transactions</a>')
            ->rawColumns(['actions'])->make(true);
    }

    // ── Withdrawals ───────────────────────────────────────────────────

    public function withdrawals()
    {
        $this->requireAny(['manage_withdrawals', 'view_transactions']);
        return view('admin.finance.withdrawals');
    }

    public function getWithdrawalsData(Request $request)
    {
        $this->requireAny(['manage_withdrawals', 'view_transactions']);
        $query = Transfer::with(['owner'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->start,  fn ($q) => $q->whereDate('created_at', '>=', $request->start))
            ->when($request->end,    fn ($q) => $q->whereDate('created_at', '<=', $request->end))
            ->latest();

        return DataTables::of($query)
            ->addColumn('owner_name',       fn ($t) => $t->owner?->name ?? '—')
            ->addColumn('owner_email',      fn ($t) => $t->owner?->email ?? '—')
            ->addColumn('formatted_amount', fn ($t) => '₦'.number_format($t->amount / 100, 2))
            ->addColumn('status_badge', fn ($t) => match ($t->status ?? 'pending') {
                'success' => '<span class="badge-success">Success</span>',
                'failed'  => '<span class="badge-danger">Failed</span>',
                default   => '<span class="badge-warning">Pending</span>',
            })
            ->addColumn('date', fn ($t) => $t->created_at->format('d M Y H:i'))
            ->rawColumns(['status_badge'])->make(true);
    }

    // ── Per-user transactions ─────────────────────────────────────────

    public function userTransactions(Request $request, int $userId)
    {
        $this->requireAny(['view_transactions', 'view_wallets']);
        $user = User::findOrFail($userId);
        $logs = TransactionLog::where('account_owner_id', $userId)
            ->where('account_owner_type', get_class($user))
            ->when($request->type, fn ($q) => $q->where('transaction_type', $request->type))
            ->orderByDesc('created_at')->paginate(25);

        $totals = TransactionLog::where('account_owner_id', $userId)
            ->where('account_owner_type', get_class($user))
            ->selectRaw('SUM(CASE WHEN transaction_type="credit" AND is_refund=0 THEN amount ELSE 0 END) as total_credit,
                         SUM(CASE WHEN transaction_type="debit" THEN amount ELSE 0 END) as total_debit')
            ->first();

        return view('admin.finance.user-transactions', compact('user', 'logs', 'totals'));
    }

    private function requireAny(array $permissions): void
    {
        if (!auth()->user()->hasAnyPermission($permissions)) abort(403);
    }
}
