@extends('layouts.app')
@section('title','Vendor Management')
@section('content')
<div class="space-y-6">
    <div><h1 class="page-title">Vendor Management</h1><p class="page-subtitle">Manage vendors, view orders and activity</p></div>
    <div class="card flex flex-wrap gap-3">
        <input type="text" id="searchInput" placeholder="Search vendors…" class="flex-1 min-w-48">
        <select id="stateFilter"><option value="">All States</option>@foreach($states as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
        <select id="categoryFilter"><option value="">All Categories</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
        <select id="statusFilter"><option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
    </div>
    <div class="card p-0 overflow-hidden">
        <table id="vendorsTable" class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Vendor</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">State</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Categories</th>
                @if(auth()->user()->hasPermission('view_wallets'))
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Wallet</th>
                @endif
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(function(){
    const showBal=@json(auth()->user()->hasPermission('view_wallets'));
    const cols=[
        {data:'vendor_name',name:'firstname',render:(d,t,r)=>`<div><p class="font-medium text-slate-900">${r.vendor_name}</p><p class="text-xs text-slate-400">${r.email}</p></div>`},
        {data:'state',name:'state.name',orderable:false},
        {data:'categories',name:'categories',orderable:false},
        ...(showBal?[{data:'wallet_balance',name:'wallet.balance'}]:[]),
        {data:'status_badge',name:'is_active',orderable:false},
        {data:'actions',name:'actions',orderable:false},
    ];
    $('#vendorsTable').DataTable({
        processing:true,serverSide:true,
        ajax:{url:'{{ route("admin.vendors.data") }}',data:d=>{d.state_id=$('#stateFilter').val();d.category_id=$('#categoryFilter').val();d.status=$('#statusFilter').val();d.search={value:$('#searchInput').val()};}},
        columns:cols,order:[[0,'asc']],pageLength:20,
    });
    $('#stateFilter,#categoryFilter,#statusFilter').on('change',()=>$('#vendorsTable').DataTable().draw());
    let t;$('#searchInput').on('keyup',()=>{clearTimeout(t);t=setTimeout(()=>$('#vendorsTable').DataTable().draw(),400);});
});
</script>
@endpush
