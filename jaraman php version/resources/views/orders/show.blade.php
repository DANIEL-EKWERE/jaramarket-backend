@extends('layouts.app')
@section('title', 'Order Details')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <a href="{{ route('orders.index') }}" class="hover:text-emerald-600 transition-colors">Orders</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium font-mono">{{ $order->reference }}</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Order Invoice</h1>
            </div>
            <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Orders
            </a>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- MAIN COLUMN --}}
            <div class="xl:col-span-8 space-y-5">

                {{-- Invoice Header --}}
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Order <span class="font-mono text-indigo-600">#{{ $order->reference }}</span></h2>
                                <p class="text-xs text-slate-400">Placed {{ $order->created_at->format('F d, Y \a\t h:i A') }}</p>
                            </div>
                        </div>
                        @php
                            $statusMap=['pending'=>'bg-amber-50 text-amber-700 border-amber-200','processing'=>'bg-blue-50 text-blue-700 border-blue-200','completed'=>'bg-emerald-50 text-emerald-700 border-emerald-200','cancelled'=>'bg-red-50 text-red-600 border-red-200'];
                            $sCls=$statusMap[$order->status]??'bg-slate-100 text-slate-500 border-slate-200';
                        @endphp
                        <span class="inline-block px-3 py-1 rounded-lg text-xs font-semibold border {{ $sCls }}">{{ ucfirst($order->status) }}</span>
                    </div>

                    {{-- Order Items Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Item</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Subtotal</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Vendor</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($order->items as $item)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-slate-100 flex-shrink-0 border border-slate-200">
                                                @if($item->product && $item->product->image_url)
                                                    <img src="{{ get_media_url($item->product->image_url) }}" class="w-full h-full object-cover" alt="{{ $item->product->name }}">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-slate-300">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <span class="font-medium text-slate-800">{{ $item->product->name ?? $item->ingredient->name ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-block px-2 py-0.5 bg-slate-100 rounded text-xs font-mono font-semibold text-slate-700">{{ number_format($item->quantity) }}</span>
                                    </td>
                                    <td class="px-4 py-4 text-right font-mono text-slate-700 text-xs">₦{{ number_format($item->price, 2) }}</td>
                                    <td class="px-4 py-4 text-right font-mono font-semibold text-slate-800 text-xs">₦{{ number_format($item->price * $item->quantity, 2) }}</td>
                                    <td class="px-4 py-4">
                                        @if($item->vendor)
                                            <div class="text-xs">
                                                <p class="font-medium text-slate-700">{{ $item->vendor->business_name }}</p>
                                                <p class="text-slate-400">{{ $item->vendor->firstname }}</p>
                                            </div>
                                        @else
                                            <span class="text-slate-300 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        @php
                                            $iStatus=$item->status??$order->status;
                                            $iMap=['pending'=>'bg-amber-50 text-amber-700','processing'=>'bg-blue-50 text-blue-700','completed'=>'bg-emerald-50 text-emerald-700','cancelled'=>'bg-red-50 text-red-600'];
                                            $iCls=$iMap[$iStatus]??'bg-slate-100 text-slate-500';
                                        @endphp
                                        <span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium {{ $iCls }}">{{ ucfirst($iStatus) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals footer --}}
                    <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                        <div class="flex justify-end">
                            <div class="w-64 space-y-2">
                                <div class="flex justify-between text-sm text-slate-600">
                                    <span>Subtotal</span>
                                    <span class="font-mono">₦{{ number_format($order->total - $order->shipping_fee, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm text-slate-600">
                                    <span>Shipping</span>
                                    <span class="font-mono">₦{{ number_format($order->shipping_fee, 2) }}</span>
                                </div>
                                <div class="border-t border-slate-200 pt-2 flex justify-between text-base font-bold text-slate-900">
                                    <span>Total</span>
                                    <span class="font-mono text-emerald-700">₦{{ number_format($order->total, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Meal Prep --}}
                @if($order->meal_prep)
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </div>
                        <h2 class="text-sm font-semibold text-slate-800">Meal Preparation Notes</h2>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-slate-600 leading-relaxed">{{ $order->meal_prep }}</p>
                    </div>
                </div>
                @endif
            </div>

            {{-- SIDEBAR --}}
            <div class="xl:col-span-4 space-y-5">

                {{-- Update Status --}}
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                        <h2 class="text-sm font-semibold text-slate-800">Update Status</h2>
                    </div>
                    <div class="p-5">
                        <form action="{{ route('orders.update.status', $order) }}" method="POST" class="space-y-3">
                            @csrf @method('PATCH')
                            @foreach(['pending'=>['Pending','bg-amber-50 text-amber-700 border-amber-200'],'processing'=>['Processing','bg-blue-50 text-blue-700 border-blue-200'],'completed'=>['Completed','bg-emerald-50 text-emerald-700 border-emerald-200'],'cancelled'=>['Cancelled','bg-red-50 text-red-600 border-red-200']] as $val=>[$label,$cls])
                            <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all {{ $order->status===$val ? 'border-emerald-300 bg-emerald-50/50' : 'border-slate-200 hover:bg-slate-50' }}">
                                <input type="radio" name="status" value="{{ $val }}" {{ $order->status===$val?'checked':'' }} class="w-4 h-4 accent-emerald-600">
                                <span class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold border {{ $cls }}">{{ $label }}</span>
                            </label>
                            @endforeach
                            <button type="submit" class="w-full mt-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                Update Status
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Customer Info --}}
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <h2 class="text-sm font-semibold text-slate-800">Customer</h2>
                    </div>
                    <div class="p-5 space-y-3">
                        <div>
                            <p class="text-xs text-slate-400">Full Name</p>
                            <p class="text-sm font-semibold text-slate-800 mt-0.5">{{ $order->user->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Phone</p>
                            <p class="text-sm text-slate-700 font-mono mt-0.5">{{ $order->user->phone_number }}</p>
                        </div>
                        @if($order->user->email)
                        <div>
                            <p class="text-xs text-slate-400">Email</p>
                            <p class="text-sm text-slate-700 mt-0.5 truncate">{{ $order->user->email }}</p>
                        </div>
                        @endif
                        <a href="{{ route('users.edit', $order->user) }}" class="block w-full text-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg transition-colors mt-2">
                            View Customer Profile
                        </a>
                    </div>
                </div>

                {{-- Order Summary --}}
                <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Order Summary</h3>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Order ID</span>
                        <span class="font-mono text-slate-700">#{{ $order->id }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Items</span>
                        <span class="font-semibold text-slate-700">{{ $order->items->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Shipping</span>
                        <span class="font-mono text-slate-700">₦{{ number_format($order->shipping_fee, 2) }}</span>
                    </div>
                    <div class="border-t border-slate-100 pt-3 flex items-center justify-between">
                        <span class="text-sm font-semibold text-slate-800">Total</span>
                        <span class="font-mono font-bold text-emerald-700 text-base">₦{{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
