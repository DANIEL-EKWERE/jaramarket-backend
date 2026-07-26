@extends('layouts.app')
@section('title','Transactions — '.$user->name)
@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.finance.wallets') }}" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg></a>
        <div><h1 class="page-title">Transactions — {{ $user->name }}</h1><p class="page-subtitle">{{ $user->email }} &bull; {{ ucfirst($user->role) }}</p></div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card"><p class="text-xs font-semibold text-slate-500 uppercase">Wallet Balance</p><p class="text-2xl font-bold text-slate-900 mt-1">₦{{ number_format($user->wallet?->balance??0,2) }}</p></div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5"><p class="text-xs font-semibold text-emerald-600 uppercase">Total Credits</p><p class="text-2xl font-bold text-emerald-800 mt-1">₦{{ number_format(($totals->total_credit??0)/100,2) }}</p></div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-5"><p class="text-xs font-semibold text-rose-600 uppercase">Total Debits</p><p class="text-2xl font-bold text-rose-800 mt-1">₦{{ number_format(($totals->total_debit??0)/100,2) }}</p></div>
    </div>
    <div class="flex gap-2">
        @foreach([''=>'All','credit'=>'Credits','debit'=>'Debits'] as $t=>$l)
        <a href="{{ route('admin.finance.user-transactions',array_filter([$user->id,'type'=>$t])) }}" class="px-4 py-2 text-sm font-medium rounded-lg {{ request('type')===$t?'bg-green-600 text-white':'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">{{ $l }}</a>
        @endforeach
    </div>
    <div class="card p-0 overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Reference</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Old Balance</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">New Balance</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Comment</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $log->reference }}</td>
                    <td class="px-4 py-3">@if($log->transaction_type==='credit')<span class="badge-success">Credit</span>@else<span class="badge-danger">Debit</span>@endif</td>
                    <td class="px-4 py-3 font-semibold {{ $log->transaction_type==='credit'?'text-emerald-700':'text-rose-700' }}">{{ $log->transaction_type==='credit'?'+':'-' }}₦{{ number_format($log->amount,2) }}</td>
                    <td class="px-4 py-3 text-slate-500">₦{{ number_format($log->old_balance/100,2) }}</td>
                    <td class="px-4 py-3 font-medium text-slate-900">₦{{ number_format($log->new_balance/100,2) }}</td>
                    <td class="px-4 py-3 text-slate-500 max-w-40 truncate">{{ $log->comment??'—' }}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                </tr>
                @empty<tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">No transactions found.</td></tr>@endforelse
            </tbody>
        </table>
        @if($logs->hasPages())<div class="px-4 py-3 border-t border-slate-100">{{ $logs->links() }}</div>@endif
    </div>
</div>
@endsection
