@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="text-slate-600 font-medium">Dashboard</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Order Management</h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-400">{{ now()->format('l, d M Y') }}</span>
                <a href="{{ route('reports.index') ?? '#' }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Reports
                </a>
            </div>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6 space-y-6">

        {{-- KPI STAT CARDS --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Orders</span>
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_orders'] ?? 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">All time</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['pending_orders'] ?? 0) }}</p>
                <p class="text-xs text-amber-500 mt-1 font-medium">Awaiting processing</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Delivered</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['delivered_orders'] ?? 0) }}</p>
                <p class="text-xs text-emerald-500 mt-1 font-medium">Successfully fulfilled</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Revenue</span>
                    <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-900">{{ $settings['currency'] ?? '₦' }}{{ number_format($stats['total_revenue'] ?? 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">Total earnings</p>
            </div>

        </div>

        {{-- ORDERS TABLE --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Recent Orders</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Live order queue — update statuses below</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <svg class="absolute inset-y-0 left-3 my-auto w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="orderSearch" placeholder="Search orders…"
                               class="pl-9 pr-4 py-2 w-44 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50">
                    </div>
                    <select id="statusFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50 text-slate-600">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full text-sm" id="ordersTable">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Order #</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Items</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50" id="ordersBody">
                        @forelse($orders ?? [] as $order)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-5 py-4 font-mono text-xs font-semibold text-slate-700">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-800">{{ $order->customer_name ?? $order->user?->name }}</p>
                                <p class="text-xs text-slate-400">{{ $order->customer_phone ?? $order->user?->phone }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $order->items_count ?? $order->orderItems?->count() ?? '—' }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-800">{{ $settings['currency'] ?? '₦' }}{{ number_format($order->total_amount, 2) }}</td>
                            <td class="px-5 py-4">
                                @php
                                    $statusColors = [
                                        'pending'    => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'processing' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'shipped'    => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'delivered'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'cancelled'  => 'bg-red-50 text-red-600 border-red-200',
                                    ];
                                    $color = $statusColors[$order->status] ?? 'bg-slate-100 text-slate-500 border-slate-200';
                                @endphp
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold border {{ $color }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-500 text-xs">{{ $order->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('orders.show', $order) ?? '#' }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                                    View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                                        <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-500">No orders yet</p>
                                    <p class="text-xs text-slate-400">Orders will appear here once customers start placing them.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($orders) && method_exists($orders, 'hasPages') && $orders->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $orders->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    function filterTable() {
        const q      = $('#orderSearch').val().toLowerCase();
        const status = $('#statusFilter').val().toLowerCase();
        $('#ordersTable tbody tr').each(function() {
            const text       = $(this).text().toLowerCase();
            const rowStatus  = $(this).find('td:eq(4)').text().toLowerCase().trim();
            const matchText  = text.includes(q);
            const matchStatus = !status || rowStatus.includes(status);
            $(this).toggle(matchText && matchStatus);
        });
    }
    $('#orderSearch').on('keyup', filterTable);
    $('#statusFilter').on('change', filterTable);
});
</script>
@endpush
