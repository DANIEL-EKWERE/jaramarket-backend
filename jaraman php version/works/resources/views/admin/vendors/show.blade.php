@extends('layouts.app')
@section('title','Vendor: '.$vendor->name)
@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.vendors.index') }}" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg></a>
        <div class="flex-1"><h1 class="page-title">{{ $vendor->name }}</h1><p class="page-subtitle">{{ $vendor->email }} &bull; {{ $vendor->phone_number }}</p></div>
        <div class="flex gap-2">
            @if(auth()->user()->hasPermission('manage_vendors'))
            <form action="{{ route('admin.vendors.toggle-status',$vendor) }}" method="POST">@csrf @method('PATCH')
                <button type="submit" class="{{ $vendor->is_active?'px-4 py-2 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100 rounded-xl':'px-4 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 rounded-xl' }}">{{ $vendor->is_active?'Deactivate':'Activate' }}</button>
            </form>
            @endif
            <a href="{{ route('admin.vendors.orders',$vendor) }}" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-xl">View Orders</a>
        </div>
    </div>

    {{-- Stats --}}
    @php $sc=['total_orders'=>['blue','Total Orders'],'pending_orders'=>['amber','Pending'],'accepted_orders'=>['teal','Accepted'],'completed_orders'=>['emerald','Completed'],'total_earned'=>['violet','Total Earned']];
    $colorMap=['blue'=>'bg-blue-50 border-blue-200 text-blue-700','amber'=>'bg-amber-50 border-amber-200 text-amber-700','teal'=>'bg-teal-50 border-teal-200 text-teal-700','emerald'=>'bg-emerald-50 border-emerald-200 text-emerald-700','violet'=>'bg-violet-50 border-violet-200 text-violet-700']; @endphp
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach($sc as $key=>[$color,$label])
        <div class="rounded-xl border p-4 {{ $colorMap[$color] }}">
            <p class="text-xs font-medium opacity-70">{{ $label }}</p>
            <p class="text-2xl font-bold mt-1">{{ $key==='total_earned'?'₦'.number_format($stats[$key],2):number_format($stats[$key]) }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Profile --}}
        <div class="card space-y-4">
            <h2 class="text-base font-semibold text-slate-900">Business Profile</h2>
            <dl class="space-y-3 text-sm">
                @foreach(['Business Name'=>$vendor->business_name,'Address'=>$vendor->business_address,'State'=>$vendor->state?->name,'Shop Size'=>$vendor->shop_size] as $l=>$v)
                <div class="flex justify-between"><dt class="text-slate-500">{{ $l }}</dt><dd class="font-medium text-slate-900 text-right max-w-40">{{ $v??'—' }}</dd></div>
                @endforeach
                <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd>@if($vendor->is_active)<span class="badge-success">Active</span>@else<span class="badge-danger">Inactive</span>@endif</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Joined</dt><dd class="font-medium text-slate-900">{{ $vendor->created_at->format('d M Y') }}</dd></div>
            </dl>
            <div class="pt-3 border-t border-slate-100">
                <p class="text-xs font-bold text-slate-500 uppercase mb-2">Categories</p>
                <div class="flex flex-wrap gap-1.5">
                    @forelse($vendor->categories as $cat)
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">{{ $cat->name }}</span>
                    @empty<span class="text-xs text-slate-400">None assigned</span>@endforelse
                </div>
            </div>
        </div>

        @if(auth()->user()->hasPermission('view_wallets'))
        <div class="card space-y-4">
            <h2 class="text-base font-semibold text-slate-900">Wallet & Bank</h2>
            <div class="rounded-xl bg-green-600 text-white p-4"><p class="text-xs text-green-200">Wallet Balance</p><p class="text-2xl font-bold mt-1">₦{{ number_format($vendor->wallet?->balance??0,2) }}</p></div>
            @foreach($vendor->bankAccounts as $bank)
            <dl class="space-y-2 text-sm border border-slate-200 rounded-xl p-3">
                <div class="flex justify-between"><dt class="text-slate-500">Bank</dt><dd class="font-medium">{{ $bank->bank_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Account Name</dt><dd class="font-medium">{{ $bank->account_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Account No.</dt><dd class="font-mono font-medium">{{ $bank->account_number }}</dd></div>
            </dl>
            @endforeach
        </div>
        @endif

        {{-- Recent orders (NO monetary data for non-accounts) --}}
        <div class="card {{ auth()->user()->hasPermission('view_wallets')?'':'lg:col-span-2' }} p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Recent Orders</h2>
                <a href="{{ route('admin.vendors.orders',$vendor) }}" class="text-xs text-green-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($recentOrders as $item)
                @php $sc2=['pending'=>'badge-warning','processing'=>'badge-info','completed'=>'badge-success','cancelled'=>'badge-danger'][$item->status]??'badge-warning'; @endphp
                <div class="flex items-center justify-between px-5 py-3">
                    <div><p class="text-sm font-medium text-slate-900">{{ $item->ingredient?->name??'N/A' }}</p><p class="text-xs text-slate-400">Order #{{ $item->order?->reference }} &bull; {{ $item->created_at->format('d M Y') }}</p></div>
                    <span class="{{ $sc2 }}">{{ ucfirst($item->status) }}</span>
                </div>
                @empty<p class="px-5 py-6 text-sm text-slate-400 text-center">No orders yet</p>@endforelse
            </div>
        </div>
    </div>
</div>
@endsection
