@extends('layouts.app')
@section('title', 'Food Management')

@section('content')
<div class="min-h-screen bg-slate-50">

    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-slate-600 font-medium">Food Management</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Product Catalogue</h1>
            </div>
            <a href="{{ route('products.create') ?? '#' }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Product
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

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-slate-200 px-5 py-4 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-44">
                <svg class="absolute inset-y-0 left-3 my-auto w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" id="productSearch" placeholder="Search products…"
                       class="pl-9 pr-4 py-2 w-full border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50">
            </div>
            <select id="categoryFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-600">
                <option value="">All Categories</option>
                @foreach($categories ?? [] as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <select id="stockFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-600">
                <option value="">All Stock</option>
                <option value="in_stock">In Stock</option>
                <option value="low_stock">Low Stock</option>
                <option value="out_of_stock">Out of Stock</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">All Products</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Prices shown at default rate — select a state for state pricing</p>
                </div>
                <span class="text-xs text-slate-400">{{ ($products ?? collect())->total() ?? 0 }} products</span>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full text-sm" id="productsTable">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Product</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Category</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Price</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($products ?? [] as $product)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if($product->image)
                                    <img src="{{ get_media_url($product->image) }}" alt="{{ $product->name }}"
                                         class="w-10 h-10 rounded-lg object-cover border border-slate-200 flex-shrink-0">
                                    @else
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $product->name }}</p>
                                        <p class="text-xs text-slate-400">SKU: {{ $product->sku ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $product->category?->name ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800">{{ $settings['currency'] ?? '₦' }}{{ number_format($product->price, 2) }}</p>
                                @if($product->discount_price)
                                <p class="text-xs text-red-500 line-through">{{ $settings['currency'] ?? '₦' }}{{ number_format($product->discount_price, 2) }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @php $stock = $product->stock ?? 0; @endphp
                                @if($stock > 10)
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ $stock }} units
                                </span>
                                @elseif($stock > 0)
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Low ({{ $stock }})
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-red-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Out of stock
                                </span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($product->is_active ?? true)
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                @else
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200">Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('products.edit', $product) ?? '#' }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        Edit
                                    </a>
                                    <form action="{{ route('products.destroy', $product) ?? '#' }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete {{ addslashes($product->name) }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-500 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                                        <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-500">No products yet</p>
                                    <a href="{{ route('products.create') ?? '#' }}" class="text-xs text-emerald-600 font-medium hover:underline">Add your first product →</a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($products) && method_exists($products, 'hasPages') && $products->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $products->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    function filterProducts() {
        const q = $('#productSearch').val().toLowerCase();
        $('#productsTable tbody tr').each(function() {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    }
    $('#productSearch').on('keyup', filterProducts);
});
</script>
@endpush
