@extends('layouts.app')
@section('title','Orders — '.$vendor->name)
@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.vendors.show',$vendor) }}" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg></a>
        <div><h1 class="page-title">Orders — {{ $vendor->name }}</h1><p class="page-subtitle">All order items for this vendor (all statuses)</p></div>
    </div>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.vendors.orders',$vendor) }}" class="px-4 py-2 text-sm font-medium rounded-lg {{ !$status?'bg-green-600 text-white':'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">All</a>
        @foreach($statuses as $s)
        <a href="{{ route('admin.vendors.orders',[$vendor,'status'=>$s]) }}" class="px-4 py-2 text-sm font-medium rounded-lg capitalize {{ $status===$s?'bg-green-600 text-white':'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">{{ ucfirst($s) }}</a>
        @endforeach
    </div>

    <div class="card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Order Ref</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Ingredient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Qty</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $item)
                    @php $sc=['pending'=>'badge-warning','processing'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger'][$item->status]??'badge-warning'; @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3"><a href="{{ route('orders.show',$item->order) }}" class="font-mono text-green-600 hover:underline font-medium">#{{ $item->order?->reference??'N/A' }}</a></td>
                        <td class="px-4 py-3"><p class="font-medium text-slate-900">{{ $item->order?->user?->name??'N/A' }}</p><p class="text-xs text-slate-400">{{ $item->order?->user?->email }}</p></td>
                        <td class="px-4 py-3 text-slate-700">{{ $item->ingredient?->name??'N/A' }}</td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $item->ingredient?->category?->name??'—' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $item->quantity }} {{ $item->unit }}</td>
                        <td class="px-4 py-3"><span class="{{ $sc }}">{{ ucfirst($item->status) }}</span></td>
                        <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">{{ $item->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @empty<tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">No orders found.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())<div class="px-4 py-3 border-t border-slate-100">{{ $orders->links() }}</div>@endif
    </div>
</div>
@endsection
