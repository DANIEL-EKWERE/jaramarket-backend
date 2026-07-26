@extends('layouts.app')
@section('title','Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="page-title">Good {{ now()->hour<12?'morning':(now()->hour<17?'afternoon':'evening') }}, {{ auth()->user()->firstname }} 👋</h1>
            <p class="page-subtitle">{{ \App\Enums\UserPermissionsEnum::from(auth()->user()->role)->label() }} @if(auth()->user()->state)&bull; {{ auth()->user()->state->name }}@endif &bull; {{ now()->format('l, d M Y') }}</p>
        </div>
        @if(auth()->user()->hasPermission('view_reports'))
        <a href="{{ route('reports.orders') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Reports
        </a>
        @endif
    </div>

    {{-- KPI Cards --}}
    @if(!empty($stats))
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
        @if(isset($stats['total_orders']))
        <div class="card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Orders</span>
                <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900">{{ number_format($stats['total_orders']) }}</p>
            <div class="flex gap-3 mt-2 text-xs">
                <span class="text-amber-600 font-medium">{{ number_format($stats['pending_orders']??0) }} pending</span>
                <span class="text-emerald-600 font-medium">{{ number_format($stats['completed_orders']??0) }} done</span>
            </div>
        </div>
        @endif
        @if(isset($stats['total_revenue']))
        <div class="card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Revenue</span>
                <div class="w-9 h-9 rounded-xl bg-violet-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900">₦{{ number_format($stats['total_revenue']??0) }}</p>
            <p class="text-xs text-slate-400 mt-2">Today: ₦{{ number_format($stats['today_revenue']??0) }}</p>
        </div>
        @endif
        @if(isset($stats['total_customers']))
        <div class="card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Customers</span>
                <div class="w-9 h-9 rounded-xl bg-teal-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900">{{ number_format($stats['total_customers']) }}</p>
            <p class="text-xs text-slate-400 mt-2">Registered customers</p>
        </div>
        @endif
        @if(isset($stats['total_vendors']))
        <div class="card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Vendors</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900">{{ number_format($stats['total_vendors']) }}</p>
            <p class="text-xs text-slate-400 mt-2">Active vendors</p>
        </div>
        @endif
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent Orders --}}
        @if($recentOrders->count())
        <div class="lg:col-span-2 card p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Recent Orders</h2>
                <a href="{{ route('orders.index') }}" class="text-xs text-green-600 hover:underline font-medium">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($recentOrders as $order)
                @php $sc = ['pending'=>'badge-warning','processing'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger'][$order->status]??'badge-warning'; @endphp
                <div class="flex items-center justify-between px-5 py-3 hover:bg-slate-50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-xs font-bold">{{ strtoupper(substr($order->user?->firstname??'U',0,1)) }}</div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $order->user?->name??'N/A' }}</p>
                            <p class="text-xs text-slate-400">#{{ $order->reference }} &bull; {{ $order->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-900">₦{{ number_format($order->total,2) }}</p>
                        <span class="{{ $sc }}">{{ ucfirst($order->status) }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Latest Users --}}
        @if($latestUsers->count())
        <div class="card p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">New Customers</h2>
                <a href="{{ route('users.index') }}" class="text-xs text-green-600 hover:underline font-medium">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($latestUsers as $u)
                <div class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50">
                    <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 text-xs font-bold">{{ strtoupper(substr($u->firstname,0,1)) }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ $u->name }}</p>
                        <p class="text-xs text-slate-400">{{ $u->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="w-2 h-2 rounded-full {{ $u->is_active?'bg-emerald-400':'bg-slate-300' }} flex-shrink-0"></span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Order Status Bar --}}
    @if(!empty($orderStatusChart)&&auth()->user()->hasPermission('view_orders'))
    <div class="card">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Orders by Status</h2>
        <div class="flex flex-wrap gap-4">
            @php
            $meta=['pending'=>['Pending','bg-amber-400'],'processing'=>['Processing','bg-blue-400'],'completed'=>['Completed','bg-emerald-400'],'cancelled'=>['Cancelled','bg-red-400']];
            $total=array_sum($orderStatusChart)?:1;
            @endphp
            @foreach($meta as $key=>[$label,$bar])
            @php $cnt=$orderStatusChart[$key]??0;$pct=round($cnt/$total*100); @endphp
            <div class="flex-1 min-w-28">
                <div class="flex justify-between text-xs text-slate-500 mb-1"><span>{{ $label }}</span><span class="font-semibold text-slate-900">{{ $cnt }}</span></div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden"><div class="{{ $bar }} h-2 rounded-full" style="width:{{ $pct }}%"></div></div>
                <p class="text-xs text-slate-400 mt-1">{{ $pct }}%</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
