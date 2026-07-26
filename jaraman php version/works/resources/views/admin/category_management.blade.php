@extends('layouts.app')
@section('title', 'Category Management')

@section('content')
<div class="min-h-screen bg-slate-50">

    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    <span class="text-slate-600 font-medium">Category Management</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Food Categories</h1>
            </div>
            <button type="button" id="openAddModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Category
            </button>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6 space-y-5">

        @if(session('success'))
        <div class="flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- Grid of categories --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($categories ?? [] as $cat)
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden hover:border-emerald-300 hover:shadow-sm transition-all group">
                <div class="h-2 bg-emerald-500"></div>
                <div class="p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            @if($cat->icon)
                                <img src="{{ get_media_url($cat->icon) }}" alt="{{ $cat->name }}" class="w-6 h-6 object-contain">
                            @else
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            @endif
                        </div>
                        @if($cat->is_active ?? true)
                        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">Active</span>
                        @else
                        <span class="text-xs font-semibold text-slate-500 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-full">Inactive</span>
                        @endif
                    </div>
                    <p class="font-semibold text-slate-800 mb-0.5">{{ $cat->name }}</p>
                    <p class="text-xs text-slate-400 mb-4">{{ $cat->products_count ?? 0 }} products</p>
                    <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                        <button type="button" data-id="{{ $cat->id }}" data-name="{{ $cat->name }}" data-slug="{{ $cat->slug }}"
                                class="edit-cat flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Edit
                        </button>
                        <form action="{{ route('categories.destroy', $cat) ?? '#' }}" method="POST" class="flex-1"
                              onsubmit="return confirm('Delete {{ addslashes($cat->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-500 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full">
                <div class="bg-white rounded-xl border border-slate-200 py-16 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No categories yet</p>
                        <button type="button" id="emptyAddBtn" class="text-xs text-emerald-600 font-medium hover:underline">Create the first category →</button>
                    </div>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ADD / EDIT MODAL --}}
<div id="catModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" id="modalOverlay"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-semibold text-slate-800" id="modalTitle">Add Category</h3>
                <button type="button" id="closeModal" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="catForm" method="POST" action="{{ route('categories.store') ?? '#' }}" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="category_id" id="catId">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Category Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="catName" required placeholder="e.g. Rice Dishes"
                           class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Slug</label>
                    <input type="text" name="slug" id="catSlug" placeholder="auto-generated"
                           class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono text-xs">
                    <p class="mt-1 text-xs text-slate-400">Leave blank to auto-generate from name.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Category Icon</label>
                    <input type="file" name="icon" accept="image/*"
                           class="text-sm text-slate-500 w-full file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>

                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked
                           class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700">Active — visible to customers</span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" id="cancelModal"
                            class="flex-1 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
                        Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    function openModal() { $('#catModal').removeClass('hidden'); }
    function closeModal() { $('#catModal').addClass('hidden'); $('#catForm')[0].reset(); $('#catId').val(''); $('#formMethod').val('POST'); $('#modalTitle').text('Add Category'); }

    $('#openAddModal, #emptyAddBtn').on('click', openModal);
    $('#closeModal, #cancelModal, #modalOverlay').on('click', closeModal);

    $(document).on('click', '.edit-cat', function() {
        const $btn = $(this);
        $('#catId').val($btn.data('id'));
        $('#catName').val($btn.data('name'));
        $('#catSlug').val($btn.data('slug'));
        $('#formMethod').val('PUT');
        $('#modalTitle').text('Edit Category');
        openModal();
    });

    // Auto slug
    $('#catName').on('input', function() {
        if (!$('#catSlug').val()) {
            $('#catSlug').attr('placeholder', $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-'));
        }
    });
});
</script>
@endpush
