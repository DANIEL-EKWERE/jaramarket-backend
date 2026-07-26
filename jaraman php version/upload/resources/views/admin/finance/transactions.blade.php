@extends('layouts.app')
@section('title','Transaction History')
@section('content')
<div class="space-y-6">
    <div><h1 class="page-title">Transaction History</h1><p class="page-subtitle">All wallet credit and debit transactions</p></div>
    <div class="card flex flex-wrap gap-3 items-end">
        <div><label>Type</label><select id="typeFilter"><option value="">All</option><option value="credit">Credit</option><option value="debit">Debit</option></select></div>
        <div><label>From</label><input type="date" id="startDate"></div>
        <div><label>To</label><input type="date" id="endDate"></div>
        <div class="flex-1 min-w-48"><label>Search user</label><input type="text" id="searchInput" placeholder="Name or email…"></div>
        <button id="clearFilters" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg">Clear</button>
    </div>
    <div class="card p-0 overflow-hidden">
        <table id="txTable" class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Reference</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Old Balance</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">New Balance</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Comment</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(function(){
    const table=$('#txTable').DataTable({
        processing:true,serverSide:true,searching:false,
        ajax:{url:'{{ route("admin.finance.transactions.data") }}',data:d=>{d.type=$('#typeFilter').val();d.start=$('#startDate').val();d.end=$('#endDate').val();d.search={value:$('#searchInput').val()};}},
        columns:[
            {data:'reference',name:'reference'},
            {data:'user_name',name:'account_owner_id',render:(d,t,r)=>`<div><p class="font-medium">${r.user_name}</p><p class="text-xs text-slate-400">${r.user_email}</p></div>`},
            {data:'type_badge',name:'transaction_type',orderable:false},
            {data:'formatted_amount',name:'amount'},
            {data:'formatted_old_balance',name:'old_balance'},
            {data:'formatted_new_balance',name:'new_balance'},
            {data:'comment',name:'comment',defaultContent:'—'},
            {data:'date',name:'created_at'},
        ],order:[[7,'desc']],pageLength:25,
    });
    $('#typeFilter,#startDate,#endDate').on('change',()=>table.draw());
    let t;$('#searchInput').on('keyup',()=>{clearTimeout(t);t=setTimeout(()=>table.draw(),400);});
    $('#clearFilters').on('click',()=>{$('#typeFilter').val('');$('#startDate,#endDate,#searchInput').val('');table.draw();});
});
</script>
@endpush
