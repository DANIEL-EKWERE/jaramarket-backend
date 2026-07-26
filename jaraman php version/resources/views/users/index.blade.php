@extends('layouts.app')
@section('title', 'Customers')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>People</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">Customers</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Customer Management</h1>
            </div>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6 space-y-5">

        {{-- STAT CARDS --}}
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Customers</p><p class="text-3xl font-bold text-slate-900 mt-1.5" id="stat-total">—</p><p class="text-xs text-slate-400 mt-2">Registered accounts</p></div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active</p><p class="text-3xl font-bold text-emerald-600 mt-1.5" id="stat-active">—</p><p class="text-xs text-slate-400 mt-2">Verified accounts</p></div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Inactive</p><p class="text-3xl font-bold text-red-400 mt-1.5" id="stat-inactive">—</p><p class="text-xs text-slate-400 mt-2">Disabled accounts</p></div>
                    <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">With Orders</p><p class="text-3xl font-bold text-blue-500 mt-1.5" id="stat-ordered">—</p><p class="text-xs text-slate-400 mt-2">Have placed orders</p></div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE CARD --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div><h2 class="text-base font-semibold text-slate-800">All Customers</h2><p class="text-xs text-slate-400 mt-0.5">Registered customer accounts</p></div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <svg class="absolute inset-y-0 left-3 my-auto w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="search" placeholder="Search customers…" class="pl-9 pr-4 py-2 w-52 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50">
                    </div>
                    <select id="status-filter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50 text-slate-700">
                        <option value="">All Customers</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="w-full overflow-x-auto">
                <table id="users-table" class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-10">#</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">First Name</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Last Name</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Phone</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Registered</th>
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
                        <h3 class="text-base font-semibold text-slate-900">Delete Customer</h3>
                        <p class="mt-1 text-sm text-slate-500">Permanently delete <strong class="text-slate-800" id="modal-name"></strong>? This cannot be undone.</p>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button id="modal-cancel" class="flex-1 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg">Cancel</button>
                    <button id="modal-confirm" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete
                    </button>
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
$(function () {

    // ── STAT CARDS ────────────────────────────────────────────────────
    // FIX 1: stats=1 tells the controller to skip the "today only" default
    //         so we get counts across ALL customers, not just today's.
    $.get('{{ route('users.data') }}', { stats: 1, length: -1, start: 0 }, function (res) {
        var rows     = res.data || [];
        var active   = 0;
        var inactive = 0;
        var ordered  = 0;

        rows.forEach(function (r) {
            // FIX 2: raw_active is now returned by the controller (was missing before)
            if (parseInt(r.raw_active) === 1) {
                active++;
            } else {
                inactive++;
            }
            // FIX 3: orders_count is now returned via withCount('orders') in controller
            if (parseInt(r.orders_count || 0) > 0) {
                ordered++;
            }
        });

        $('#stat-total').text(res.recordsTotal || rows.length);
        $('#stat-active').text(active);
        $('#stat-inactive').text(inactive);
        $('#stat-ordered').text(ordered);
    });

    // ── DATATABLE ─────────────────────────────────────────────────────
    var table = $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  '{{ route('users.data') }}',
            data: function (d) {
                d.status = $('#status-filter').val();
                d.search = $('#search').val();
            }
        },
        columns: [
            // FIX 4: Use meta.row instead of data:'DT_RowIndex' — works without
            //         any extra server-side field and resets correctly per page.
            {
                data:       null,
                orderable:  false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return '<span class="text-slate-400 text-xs font-mono">' + (meta.row + 1) + '</span>';
                }
            },
            {
                data:   'firstname',
                render: function (d) {
                    return '<span class="font-medium text-slate-800">' + (d || '—') + '</span>';
                }
            },
            {
                data:   'lastname',
                render: function (d) {
                    return '<span class="text-slate-700">' + (d || '—') + '</span>';
                }
            },
            {
                data:           'phone_number',
                defaultContent: '—',
                render: function (d) {
                    return '<span class="font-mono text-slate-600 text-xs">' + (d || '—') + '</span>';
                }
            },
            {
                data:   'email',
                render: function (d) {
                    return '<span class="text-slate-500 text-xs">' + (d || '—') + '</span>';
                }
            },
            {
                data:   'created_at',
                render: function (d) {
                    return '<span class="text-slate-400 text-xs whitespace-nowrap">' + (d || '—') + '</span>';
                }
            },
            { data: 'status',  orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-right' }
        ],
        order:      [[5, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            emptyTable:  'No customers found',
            zeroRecords: 'No customers match your filters',
            processing:  '<div class="flex items-center justify-center gap-2 py-6 text-slate-400 text-sm">'
                       + '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">'
                       + '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>'
                       + '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>Loading…</div>',
            paginate: { previous: '‹', next: '›' }
        },
        dom: '<"flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 px-2"l>'
           + 't'
           + '<"flex flex-col sm:flex-row sm:items-center sm:justify-between border-t border-slate-100 px-2"ip>'
    });

    var searchTimer;
    $('#search').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { table.ajax.reload(null, false); }, 350);
    });
    $('#status-filter').on('change', function () { table.ajax.reload(null, false); });

    // ── DELETE MODAL ──────────────────────────────────────────────────
    var pendingId = null;

    function openModal(id, name) {
        pendingId = id;
        $('#modal-name').text(name || 'this customer');
        $('#delete-modal').removeClass('hidden');
        $('body').css('overflow', 'hidden');
    }
    function closeModal() {
        $('#delete-modal').addClass('hidden');
        $('body').css('overflow', '');
        pendingId = null;
    }

    // FIX 5: data-name is now populated by the controller on every delete button
    $(document).on('click', '.delete-user', function () {
        openModal($(this).data('user-id'), $(this).data('name'));
    });
    $('#modal-cancel, #modal-backdrop').on('click', closeModal);
    $(document).on('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

    $('#modal-confirm').on('click', function () {
        if (!pendingId) return;
        $.ajax({
            url:     '/users/' + pendingId,
            type:    'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                closeModal();
                table.ajax.reload(null, false);
                showToast('Customer deleted successfully.', 'success');
            },
            error: function () {
                closeModal();
                showToast('Delete failed. Please try again.', 'error');
            }
        });
    });

    function showToast(msg, type) {
        var cls = type === 'success' ? 'bg-emerald-700' : 'bg-red-700';
        var $t  = $('<div class="fixed bottom-6 right-6 z-[9999] flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-white text-sm font-medium ' + cls + '" style="opacity:0;transform:translateY(8px);transition:all .2s"><span>' + msg + '</span></div>').appendTo('body');
        requestAnimationFrame(function () { $t.css({ opacity: 1, transform: 'translateY(0)' }); });
        setTimeout(function () {
            $t.css({ opacity: 0, transform: 'translateY(8px)' });
            setTimeout(function () { $t.remove(); }, 300);
        }, 3500);
    }

});
</script>
@endpush
