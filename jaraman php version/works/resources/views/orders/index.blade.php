@extends('layouts.app')
@section('title', 'Orders')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Operations</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">Orders</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Order Management</h1>
            </div>
            <a href="{{ route('orders.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                New Order
            </a>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6 space-y-5">

        @if(session('success'))
        <div class="flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- STAT CARDS --}}
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Orders</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1.5" id="stat-total">—</p>
                        <p class="text-xs text-slate-400 mt-2">All time</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Processing</p>
                        <p class="text-3xl font-bold text-amber-500 mt-1.5" id="stat-processing">—</p>
                        <p class="text-xs text-slate-400 mt-2">Awaiting fulfilment</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Completed</p>
                        <p class="text-3xl font-bold text-emerald-600 mt-1.5" id="stat-completed">—</p>
                        <p class="text-xs text-slate-400 mt-2">Successfully fulfilled</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Revenue</p>
                        <p class="text-2xl font-bold text-blue-600 mt-1.5 font-mono" id="stat-revenue">—</p>
                        <p class="text-xs text-slate-400 mt-2">Completed orders</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE CARD --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">All Orders</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Filter, search and manage orders</p>
                </div>
                {{--
                    FIX #1 — Custom search & filter inputs.
                    We use our OWN inputs and suppress DataTables' built-in
                    search box via dom:'t...' (no 'f'). This prevents the
                    double-search conflict where DataTables' internal search
                    parameter was being overwritten.
                --}}
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <svg class="absolute inset-y-0 left-3 my-auto w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="dt-search" placeholder="Search orders…" class="pl-9 pr-4 py-2 w-52 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50">
                    </div>
                    <select id="dt-status" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50 text-slate-700">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="w-full overflow-x-auto">
                {{--
                    FIX #2 — thead must match EXACTLY the columns array in JS.
                    Original table.blade.php had 8 columns (including Meal Prep).
                    index.blade.php only declared 7 JS columns → DataTables
                    "Cannot read properties of undefined" error on column count mismatch.
                    We now have 8 <th> matching 8 JS column definitions below.
                --}}
                <table id="orders-table" class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-10">#</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Reference</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount (₦)</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Meal Prep</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
div.dataTables_wrapper { font-size: .8rem; color: #475569; }
div.dataTables_wrapper div.dataTables_length { padding: .875rem 1.5rem .5rem; }
div.dataTables_wrapper div.dataTables_length select { border: 1px solid #cbd5e1; border-radius: .5rem; padding: .35rem .65rem; font-size: .8rem; outline: none; background: #f8fafc; color: #334155; }
div.dataTables_wrapper div.dataTables_info { padding: .875rem 1.5rem; font-size: .775rem; color: #94a3b8; }
div.dataTables_wrapper div.dataTables_paginate { padding: .875rem 1.5rem; }
div.dataTables_wrapper div.dataTables_paginate .paginate_button { padding: .35rem .75rem !important; margin: 0 1px; border-radius: .5rem !important; border: 1px solid transparent !important; font-size: .78rem !important; color: #475569 !important; line-height: 1.4; }
div.dataTables_wrapper div.dataTables_paginate .paginate_button:hover { background: #f1f5f9 !important; border-color: #e2e8f0 !important; color: #0f172a !important; }
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current,
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current:hover { background: #059669 !important; border-color: #059669 !important; color: #fff !important; }
table.dataTable thead th { border-bottom: none !important; }
table.dataTable.no-footer { border-bottom: none !important; }
table.dataTable tbody tr:hover > td { background: #f8fafc !important; }
table.dataTable tbody td { padding: .875rem 1rem; vertical-align: middle; }
</style>
@endsection

@push('scripts')
<script>
$(function () {

    // ── Status badge renderer ──────────────────────────────────────────
    function fmtStatus(s) {
        var map = {
            pending:    'bg-amber-50 text-amber-700 border-amber-200',
            processing: 'bg-blue-50 text-blue-700 border-blue-200',
            completed:  'bg-emerald-50 text-emerald-700 border-emerald-200',
            cancelled:  'bg-red-50 text-red-600 border-red-200'
        };
        var cls = map[s] || 'bg-slate-100 text-slate-500 border-slate-200';
        var dot = {
            pending: 'bg-amber-400', processing: 'bg-blue-500',
            completed: 'bg-emerald-500', cancelled: 'bg-red-500'
        }[s] || 'bg-slate-400';
        return '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border ' + cls + '">'
             + '<span class="w-1.5 h-1.5 rounded-full ' + dot + '"></span>'
             + (s ? s.charAt(0).toUpperCase() + s.slice(1) : '—')
             + '</span>';
    }

    // ── Load stat cards (separate full-fetch, uses raw_* fields) ──────
    // NOTE: 'search' here is a flat string key — DataTables internally uses
    // the OBJECT search[value], so a flat d.search string is safe and matches
    // what the server controller already reads as $request->search.
    function loadStats() {
        $.get('{{ route('orders.data') }}', { length: -1, start: 0, search: '' }, function (res) {
            var rows       = res.data || [];
            var processing = 0, completed = 0, revenue = 0;

            rows.forEach(function (r) {
                var st = (r.raw_status || '').toLowerCase();
                if (st === 'processing' || st === 'pending') { processing++; }
                if (st === 'completed') { completed++; revenue += parseFloat(r.raw_total || 0); }
            });

            $('#stat-total').text(res.recordsTotal || rows.length);
            $('#stat-processing').text(processing);
            $('#stat-completed').text(completed);
            $('#stat-revenue').text('₦' + revenue.toLocaleString('en-NG', { minimumFractionDigits: 2 }));
        });
    }
    loadStats();

    // ── DataTable init ─────────────────────────────────────────────────
    var table = $('#orders-table').DataTable({
        processing:  true,
        serverSide:  true,

        // Send our custom inputs under the SAME param names the controller
        // already reads: $request->search and $request->status.
        // DataTables' own internal search param is search[value] (an OBJECT key),
        // so a flat string d.search does NOT collide with it.
        ajax: {
            url:  '{{ route('orders.data') }}',
            data: function (d) {
                d.search = $('#dt-search').val();
                d.status = $('#dt-status').val();
            }
        },

        // FIX #6 — 8 column definitions to match the 8 <th> elements above.
        //           Original had only 7, causing "Cannot set property 'mData'
        //           of undefined" / column-count mismatch error.
        columns: [
            // FIX #7 — DT_RowIndex must be explicitly added server-side.
            //           Use render callback that receives (data, type, row, meta)
            //           — meta.row gives the 0-based draw-relative row number.
            {
                data:       null,
                orderable:  false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return '<span class="text-slate-400 text-xs font-mono">' + (meta.row + 1) + '</span>';
                }
            },
            {
                data:   'reference',
                render: function (d) {
                    return '<span class="font-mono font-semibold text-slate-800 text-xs bg-slate-100 px-1.5 py-0.5 rounded">' + (d || '—') + '</span>';
                }
            },
            {
                data:   'customer',
                render: function (d) {
                    return '<span class="font-medium text-slate-700">' + (d || '—') + '</span>';
                }
            },
            {
                data:       'total',
                className:  'text-right',
                render: function (d) {
                    return '<span class="font-mono font-semibold text-slate-800">₦' + (d || '0.00') + '</span>';
                }
            },
            {
                data:   'status',
                render: function (d) { return fmtStatus(d || ''); }
            },
            // FIX #8 — Added missing meal_prep column (was in table.blade.php but absent in JS).
            {
                data:   'meal_prep',
                render: function (d) {
                    var text = d || 'No instructions';
                    return '<span class="text-slate-500 text-xs block max-w-xs truncate" title="' + text + '">' + text + '</span>';
                }
            },
            {
                data:   'created_at',
                render: function (d) {
                    return '<span class="text-slate-400 text-xs whitespace-nowrap">' + (d || '—') + '</span>';
                }
            },
            {
                data:       'actions',
                orderable:  false,
                searchable: false,
                className:  'text-right'
            }
        ],

        language: {
            emptyTable:  'No orders found',
            zeroRecords: 'No orders match your filters',
            processing:  '<div class="flex items-center justify-center gap-2 py-6 text-slate-400 text-sm">'
                       + '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">'
                       + '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>'
                       + '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>Loading…</div>',
            paginate:    { previous: '‹', next: '›' }
        },

        order:      [[6, 'desc']],   // sort by Date column (index 6, not 5 — shifted by meal_prep)
        pageLength: 25,
        responsive: true,

        // FIX #9 — Removed 'f' from dom so DataTables does NOT render its own
        //           search box. Our custom #dt-search input handles search instead.
        //           Keeping 'l' (length control) and 'p' (pagination) + 'i' (info).
        dom: '<"flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 px-2"l>'
           + 't'
           + '<"flex flex-col sm:flex-row sm:items-center sm:justify-between border-t border-slate-100 px-2"ip>'
    });

    // ── Wire custom search + filter to DataTable reload ───────────────
    // Wire custom inputs — server-side filtering handled via the ajax
    // data callback above (d.search and d.status).
    var searchTimer;
    $('#dt-search').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            table.ajax.reload(null, false); // false = keep current page position
        }, 350);
    });

    $('#dt-status').on('change', function () {
        table.ajax.reload(null, false);
    });

});
</script>
@endpush
