@extends('layouts.app')
@section('title', 'Products')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Catalogue</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">Products</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Product Catalogue</h1>
            </div>
            <a href="{{ route('products.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                New Product
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
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Products</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1.5" id="stat-total">—</p>
                        <p class="text-xs text-slate-400 mt-2">All catalogue items</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">In Stock</p>
                        <p class="text-3xl font-bold text-emerald-600 mt-1.5" id="stat-in">—</p>
                        <p class="text-xs text-slate-400 mt-2">Stock &gt; 10 units</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Low Stock</p>
                        <p class="text-3xl font-bold text-amber-500 mt-1.5" id="stat-low">—</p>
                        <p class="text-xs text-slate-400 mt-2">Between 1–10 units</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Out of Stock</p>
                        <p class="text-3xl font-bold text-red-500 mt-1.5" id="stat-out">—</p>
                        <p class="text-xs text-slate-400 mt-2">Zero inventory</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                </div>
            </div>

        </div>

        {{-- TABLE CARD --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">

            {{-- Card header with filters --}}
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">All Products</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Search, filter and manage your product catalogue</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <svg class="absolute inset-y-0 left-3 my-auto w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" id="search" placeholder="Search products…"
                               class="pl-9 pr-4 py-2 w-52 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50 transition-all">
                    </div>
                    <select id="category" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50 text-slate-700 transition-all">
                        <option value="">All Categories</option>
                        @foreach (\App\Models\Category::orderBy('name')->get() as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <select id="stock" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50 text-slate-700 transition-all">
                        <option value="">All Stock Levels</option>
                        <option value="in_stock">In Stock (&gt;10)</option>
                        <option value="low_stock">Low Stock (1–10)</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
            </div>

            {{-- Table --}}
            <div class="w-full overflow-x-auto">
                <table id="products-table" class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-10">#</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-14">Image</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Product</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Price</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Categories</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Rating</th>
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
<div id="delete-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="modal-backdrop"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="relative bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl animate-modal">
            <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto mt-3 mb-1 sm:hidden"></div>
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-full bg-red-100 flex-shrink-0 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Delete Product</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            You're about to permanently delete <strong class="text-slate-800" id="modal-product-name"></strong>.
                            This cannot be undone.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button id="modal-cancel" class="flex-1 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button id="modal-confirm" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete Product
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* DataTables overrides */
div.dataTables_wrapper { font-size:.8rem; color:#475569; }
div.dataTables_wrapper div.dataTables_length,
div.dataTables_wrapper div.dataTables_filter { padding:.875rem 1.5rem .5rem; }
div.dataTables_wrapper div.dataTables_length select,
div.dataTables_wrapper div.dataTables_filter input {
    border:1px solid #cbd5e1; border-radius:.5rem; padding:.35rem .65rem;
    font-size:.8rem; outline:none; background:#f8fafc; color:#334155;
}
div.dataTables_wrapper div.dataTables_filter input:focus { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.12); }
div.dataTables_wrapper div.dataTables_info { padding:.875rem 1.5rem; font-size:.775rem; color:#94a3b8; }
div.dataTables_wrapper div.dataTables_paginate { padding:.875rem 1.5rem; }
div.dataTables_wrapper div.dataTables_paginate .paginate_button {
    padding:.35rem .75rem !important; margin:0 1px; border-radius:.5rem !important;
    border:1px solid transparent !important; font-size:.78rem !important; color:#475569 !important; line-height:1.4;
}
div.dataTables_wrapper div.dataTables_paginate .paginate_button:hover { background:#f1f5f9 !important; border-color:#e2e8f0 !important; color:#0f172a !important; }
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current,
div.dataTables_wrapper div.dataTables_paginate .paginate_button.current:hover { background:#059669 !important; border-color:#059669 !important; color:#fff !important; }
div.dataTables_wrapper div.dataTables_paginate .paginate_button.disabled { color:#cbd5e1 !important; }
table.dataTable thead th { border-bottom:none !important; }
table.dataTable.no-footer { border-bottom:none !important; }
table.dataTable tbody tr { transition:background .1s; }
table.dataTable tbody tr:hover > td { background:#f8fafc !important; }
table.dataTable tbody td { padding:.875rem 1.25rem; vertical-align:middle; }
.badge-in  { display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .7rem;border-radius:9999px;font-size:.7rem;font-weight:600;background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0; }
.badge-low { display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .7rem;border-radius:9999px;font-size:.7rem;font-weight:600;background:#fef9c3;color:#a16207;border:1px solid #fef08a; }
.badge-out { display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .7rem;border-radius:9999px;font-size:.7rem;font-weight:600;background:#fee2e2;color:#dc2626;border:1px solid #fecaca; }
@keyframes modal-in { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
.animate-modal { animation:modal-in .2s ease-out both; }
</style>
@endsection

@push('scripts')
<script>
$(function () {

    function loadStats() {
        $.get('{{ route('products.data') }}', { length: -1, start: 0 }, function (res) {
            const rows = res.data || [];
            let inS = 0, low = 0, out = 0;
            rows.forEach(r => {
                const s = parseInt(r.raw_stock ?? 0);
                if (s > 10) inS++; else if (s > 0) low++; else out++;
            });
            $('#stat-total').text(res.recordsTotal ?? rows.length);
            $('#stat-in').text(inS); $('#stat-low').text(low); $('#stat-out').text(out);
        });
    }
    loadStats();

    const table = $('#products-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('products.data') }}',
            data: d => { d.search = $('#search').val(); d.category = $('#category').val(); d.stock = $('#stock').val(); }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false,
              render: d => `<span class="text-slate-400 text-xs font-mono">${d}</span>` },
            { data: 'image', orderable: false, searchable: false,
              render: d => d
                ? `<img src="${d}" class="w-10 h-10 rounded-lg object-cover border border-slate-100">`
                : `<div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center"><svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>` },
            { data: 'name', render: d => `<span class="font-semibold text-slate-800">${d}</span>` },
            { data: 'price', orderable: false },
            { data: 'stock', orderable: false },
            { data: 'categories', orderable: false, searchable: false,
              render: d => Array.isArray(d) && d.length
                ? d.map(c => `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100 mr-1 mb-0.5">${c.name}</span>`).join('')
                : `<span class="text-slate-300 text-xs">—</span>` },
            { data: 'rating', orderable: false, searchable: false,
              render: d => {
                if (!d) return `<span class="text-slate-300 text-xs">No rating</span>`;
                const n = parseFloat(d), full = Math.round(n);
                const stars = [1,2,3,4,5].map(i =>
                    `<svg class="w-3.5 h-3.5 ${i<=full?'text-amber-400':'text-slate-200'}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>`
                ).join('');
                return `<div class="flex items-center gap-1"><div class="flex">${stars}</div><span class="text-xs text-slate-500 ml-1">${n.toFixed(1)}</span></div>`;
              }
            },
            { data: 'actions', orderable: false, searchable: false, className: 'text-right' }
        ],
        language: {
            emptyTable:  'No products found',
            zeroRecords: 'No products match your filters',
            processing:  '<div class="flex items-center justify-center gap-2 py-6 text-slate-400 text-sm"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>Loading…</div>',
            paginate: { previous: '‹', next: '›' },
            lengthMenu: 'Show _MENU_ per page',
            info: '_START_–_END_ of _TOTAL_ products'
        },
        order: [[2, 'asc']],
        dom: '<"flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 px-2"lf>t<"flex flex-col sm:flex-row sm:items-center sm:justify-between border-t border-slate-100 px-2"ip>',
        responsive: true,
        pageLength: 25
    });

    let searchTimer;
    $('#search').on('keyup', () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => table.ajax.reload(), 350); });
    $('#category, #stock').on('change', () => table.ajax.reload());

    let pendingId = null;
    function openModal(id, name) { pendingId = id; $('#modal-product-name').text(name || 'this product'); $('#delete-modal').removeClass('hidden'); $('body').css('overflow', 'hidden'); }
    function closeModal() { $('#delete-modal').addClass('hidden'); $('body').css('overflow', ''); pendingId = null; }

    $(document).on('click', '.delete-product', function () { openModal($(this).data('product-id'), $(this).data('product-name')); });
    $('#modal-cancel, #modal-backdrop').on('click', closeModal);
    $(document).on('keydown', e => { if (e.key === 'Escape') closeModal(); });

    $('#modal-confirm').on('click', function () {
        if (!pendingId) return;
        const $btn = $(this).prop('disabled', true).html('<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Deleting…');
        $.ajax({
            url: `/products/${pendingId}`, type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: res => { closeModal(); if (res.success) { table.ajax.reload(null, false); loadStats(); showToast('Product deleted successfully.', 'success'); } else showToast(res.message || 'Could not delete.', 'error'); },
            error: xhr => { closeModal(); showToast(xhr.responseJSON?.message || 'Delete failed.', 'error'); },
            complete: () => $btn.prop('disabled', false).html('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Delete Product')
        });
    });

    function showToast(msg, type) {
        const ok = type === 'success';
        const bg = ok ? 'bg-emerald-700' : 'bg-red-700';
        const icon = ok
            ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'
            : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>';
        const $t = $(`<div class="fixed bottom-6 right-6 z-[9999] flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-white text-sm font-medium ${bg}" style="opacity:0;transform:translateY(8px);transition:all .2s"><svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">${icon}</svg><span>${msg}</span></div>`).appendTo('body');
        requestAnimationFrame(() => $t.css({ opacity: 1, transform: 'translateY(0)' }));
        setTimeout(() => { $t.css({ opacity: 0, transform: 'translateY(8px)' }); setTimeout(() => $t.remove(), 300); }, 3500);
    }
});
</script>
@endpush
