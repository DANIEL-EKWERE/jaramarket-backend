@extends('layouts.app')
@section('title','Wallet Balances')
@section('content')
<div class="space-y-6">
    <div><h1 class="page-title">Wallet Balances</h1><p class="page-subtitle">Overview of all wallet balances</p></div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer Balances</p><p class="text-2xl font-bold text-slate-900 mt-1">₦{{ number_format($summary['total_user_balance'],2) }}</p></div>
        <div class="card"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Vendor Balances</p><p class="text-2xl font-bold text-slate-900 mt-1">₦{{ number_format($summary['total_vendor_balance'],2) }}</p></div>
        <div class="card"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Wallets</p><p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($summary['total_wallets']) }}</p></div>
    </div>
    <div class="card flex flex-wrap gap-3">
        <select id="roleFilter"><option value="">All Roles</option><option value="customer">Customers</option><option value="vendor">Vendors</option></select>
        <input type="number" id="minBalance" placeholder="Min balance (₦)…">
    </div>
    <div class="card p-0 overflow-hidden">
        <table id="walletsTable" class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Role</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Balance</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Actions</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(function(){
    const table=$('#walletsTable').DataTable({
        processing:true,serverSide:true,
        ajax:{url:'{{ route("admin.finance.wallets.data") }}',data:d=>{d.role=$('#roleFilter').val();d.min_balance=$('#minBalance').val();}},
        columns:[
            {data:'user_name',name:'user.firstname',render:(d,t,r)=>`<div><p class="font-medium">${r.user_name}</p><p class="text-xs text-slate-400">${r.user_email}</p></div>`},
            {data:'user_role',name:'user.role'},
            {data:'formatted_balance',name:'balance'},
            {data:'actions',name:'actions',orderable:false},
        ],order:[[2,'desc']],pageLength:25,
    });
    $('#roleFilter').on('change',()=>table.draw());
    let t;$('#minBalance').on('keyup',()=>{clearTimeout(t);t=setTimeout(()=>table.draw(),500);});
});
</script>
@endpush
