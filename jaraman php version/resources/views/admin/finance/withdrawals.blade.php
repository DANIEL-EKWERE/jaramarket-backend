@extends('layouts.app')
@section('title','Withdrawals')
@section('content')
<div class="space-y-6">
    <div><h1 class="page-title">Withdrawals</h1><p class="page-subtitle">All bank transfer / withdrawal records</p></div>
    <div class="card flex flex-wrap gap-3 items-end">
        <div><label>Status</label><select id="statusFilter"><option value="">All</option><option value="success">Success</option><option value="pending">Pending</option><option value="failed">Failed</option></select></div>
        <div><label>From</label><input type="date" id="startDate"></div>
        <div><label>To</label><input type="date" id="endDate"></div>
    </div>
    <div class="card p-0 overflow-hidden">
        <table id="withdrawalsTable" class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Reference</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
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
    const table=$('#withdrawalsTable').DataTable({
        processing:true,serverSide:true,
        ajax:{url:'{{ route("admin.finance.withdrawals.data") }}',data:d=>{d.status=$('#statusFilter').val();d.start=$('#startDate').val();d.end=$('#endDate').val();}},
        columns:[
            {data:'owner_name',name:'owner.firstname',render:(d,t,r)=>`<div><p class="font-medium">${r.owner_name}</p><p class="text-xs text-slate-400">${r.owner_email}</p></div>`},
            {data:'formatted_amount',name:'amount'},
            {data:'reference',name:'reference',defaultContent:'—'},
            {data:'status_badge',name:'status',orderable:false},
            {data:'date',name:'created_at'},
        ],order:[[4,'desc']],pageLength:25,
    });
    $('#statusFilter,#startDate,#endDate').on('change',()=>table.draw());
});
</script>
@endpush
