@extends('layouts.app')
@section('title', 'Vendors')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Partners</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">Vendors</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Vendor Management</h1>
            </div>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6 space-y-5">

        {{-- STAT CARDS --}}
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Vendors</p><p class="text-3xl font-bold text-slate-900 mt-1.5" id="stat-total">—</p><p class="text-xs text-slate-400 mt-2">All registered</p></div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active</p><p class="text-3xl font-bold text-emerald-600 mt-1.5" id="stat-active">—</p><p class="text-xs text-slate-400 mt-2">Verified partners</p></div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Inactive</p><p class="text-3xl font-bold text-red-400 mt-1.5" id="stat-inactive">—</p><p class="text-xs text-slate-400 mt-2">Suspended accounts</p></div>
                    <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Shop Sizes</p><p class="text-3xl font-bold text-orange-500 mt-1.5" id="stat-sizes">—</p><p class="text-xs text-slate-400 mt-2">Distinct categories</p></div>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE CARD --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div><h2 class="text-base font-semibold text-slate-800">All Vendors</h2><p class="text-xs text-slate-400 mt-0.5">Registered vendor / business partners</p></div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <svg class="absolute inset-y-0 left-3 my-auto w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="search" placeholder="Search vendors…" class="pl-9 pr-4 py-2 w-52 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50">
                    </div>
                    <select id="status-filter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50 text-slate-700">
                        <option value="">All Vendors</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="w-full overflow-x-auto">
                <table id="vendors-table" class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-10">#</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Business</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Phone</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Address</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Shop Size</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Joined</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- DELETE MODAL --}}
<div id="delete-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="modal-backdrop"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="relative bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl animate-modal">
            <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto mt-3 mb-1 sm:hidden"></div>
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-full bg-red-100 flex-shrink-0 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Delete Vendor</h3>
                        <p class="mt-1 text-sm text-slate-500">Permanently delete <strong class="text-slate-800" id="modal-name"></strong>? This cannot be undone.</p>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button id="modal-cancel" class="flex-1 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg">Cancel</button>
                    <button id="modal-confirm" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg inline-flex items-center justify-center gap-2">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
div.dataTables_wrapper{font-size:.8rem;color:#475569}
div.dataTables_wrapper div.dataTables_length,div.dataTables_wrapper div.dataTables_filter{padding:.875rem 1.5rem .5rem}
div.dataTables_wrapper div.dataTables_length select,div.dataTables_wrapper div.dataTables_filter input{border:1px solid #cbd5e1;border-radius:.5rem;padding:.35rem .65rem;font-size:.8rem;outline:none;background:#f8fafc}
div.dataTables_wrapper div.dataTables_filter input:focus{border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.12)}
div.dataTables_wrapper div.dataTables_info{padding:.875rem 1.5rem;font-size:.775rem;color:#94a3b8}
div.dataTables_wrapper div.dataTables_paginate{padding:.875rem 1.5rem}
div.dataTables_wrapper div.dataTables_paginate .paginate_button{padding:.35rem .75rem!important;margin:0 1px;border-radius:.5rem!important;border:1px solid transparent!important;font-size:.78rem!important;color:#475569!important}
div.dataTables_wrapper div.dataTables_paginate .paginate_button:hover{background:#f1f5f9!important;border-color:#e2e8f0!important;color:#0f172a!important}
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current,div.dataTables_wrapper div.dataTables_paginate .paginate_button.current:hover{background:#059669!important;border-color:#059669!important;color:#fff!important}
table.dataTable thead th{border-bottom:none!important}table.dataTable.no-footer{border-bottom:none!important}
table.dataTable tbody tr:hover>td{background:#f8fafc!important}table.dataTable tbody td{padding:.875rem 1rem;vertical-align:middle}
@keyframes modal-in{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}.animate-modal{animation:modal-in .2s ease-out both}
</style>
@endsection

@push('scripts')
<script>
$(function(){
    $.get('{{ route('vendors.data') }}',{length:-1,start:0},function(res){
        const rows=res.data||[];let active=0,inactive=0,sizes=new Set();
        rows.forEach(r=>{if(r.raw_active) active++;else inactive++;if(r.shop_size) sizes.add(r.shop_size);});
        $('#stat-total').text(res.recordsTotal??rows.length);$('#stat-active').text(active);$('#stat-inactive').text(inactive);$('#stat-sizes').text(sizes.size||'—');
    });

    const table=$('#vendors-table').DataTable({
        processing:true,serverSide:true,
        ajax:{url:'{{ route('vendors.data') }}',data:d=>{d.status=$('#status-filter').val();d.search=$('#search').val();}},
        columns:[
            {data:'DT_RowIndex',orderable:false,searchable:false,render:d=>`<span class="text-slate-400 text-xs font-mono">${d}</span>`},
            {data:'firstname',render:(d,t,r)=>`<div><p class="font-semibold text-slate-800">${r.firstname} ${r.lastname}</p></div>`},
            {data:'business_name',render:d=>`<span class="font-medium text-indigo-700 text-xs">${d||'—'}</span>`},
            {data:'phone_number',render:d=>`<span class="font-mono text-slate-600 text-xs">${d}</span>`},
            {data:'email',render:d=>`<span class="text-slate-500 text-xs">${d}</span>`},
            {data:'business_address',render:d=>`<span class="text-slate-400 text-xs max-w-32 block truncate">${d||'—'}</span>`},
            {data:'shop_size',render:d=>d?`<span class="inline-block px-2 py-0.5 bg-orange-50 text-orange-700 border border-orange-100 rounded-md text-xs font-medium">${d}</span>`:'<span class="text-slate-300 text-xs">—</span>'},
            {data:'created_at',render:d=>`<span class="text-slate-400 text-xs">${d}</span>`},
            {data:'status',orderable:false,searchable:false},
            {data:'actions',orderable:false,searchable:false,className:'text-right'}
        ],
        order:[[7,'desc']],pageLength:25,responsive:true,
        language:{emptyTable:'No vendors found',paginate:{previous:'‹',next:'›'}},
        dom:'<"flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 px-2"lf>t<"flex flex-col sm:flex-row sm:items-center sm:justify-between border-t border-slate-100 px-2"ip>'
    });

    let st;$('#search').on('keyup',()=>{clearTimeout(st);st=setTimeout(()=>table.ajax.reload(),350);});
    $('#status-filter').on('change',()=>table.ajax.reload());

    let pendingId=null;
    function openModal(id,name){pendingId=id;$('#modal-name').text(name||'this vendor');$('#delete-modal').removeClass('hidden');$('body').css('overflow','hidden');}
    function closeModal(){$('#delete-modal').addClass('hidden');$('body').css('overflow','');pendingId=null;}
    $(document).on('click','.delete-vendor',function(){openModal($(this).data('vendor-id'),$(this).data('name'));});
    $('#modal-cancel,#modal-backdrop').on('click',closeModal);
    $(document).on('keydown',e=>{if(e.key==='Escape')closeModal();});
    $('#modal-confirm').on('click',function(){
        if(!pendingId)return;
        $.ajax({url:`/vendors/${pendingId}`,type:'DELETE',headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},
            success:()=>{closeModal();table.ajax.reload(null,false);},error:()=>{closeModal();}
        });
    });
});
</script>
@endpush
