@extends('layouts.app')
@section('title', 'Edit Category')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <a href="{{ route('categories.index') }}" class="hover:text-emerald-600 transition-colors">Categories</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium truncate max-w-xs">{{ $category->name }}</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Edit Category</h1>
            </div>
            <a href="{{ route('categories.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Categories
            </a>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6">

        @if(session('success'))
        <div class="mb-5 flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-800 mb-1">Please fix {{ $errors->count() }} error(s):</p>
                    <ul class="text-sm text-red-700 list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('categories.update', $category) }}" method="POST" enctype="multipart/form-data" id="category-form">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

                {{-- ═══════════════════════════════════════════
                     LEFT COLUMN (xl:col-span-8)
                ═══════════════════════════════════════════ --}}
                <div class="xl:col-span-8 space-y-5">

                    {{-- ▌SECTION 1 · Category Details --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/60">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Category Details</h2>
                                <p class="text-xs text-slate-400">Name, type and display order</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-5">

                            {{-- Name --}}
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Category Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}"
                                       placeholder="e.g. Main Course"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors
                                              @error('name') border-red-400 bg-red-50 @else border-slate-300 bg-slate-50 @enderror">
                                @error('name')
                                    <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- Type + Sort Order --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label for="category_type_id" class="block text-sm font-medium text-slate-700 mb-1.5">Type</label>
                                    <select name="category_type_id" id="category_type_id"
                                            class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors
                                                   @error('category_type_id') border-red-400 bg-red-50 @enderror">
                                        <option value="">— Select Type —</option>
                                        @foreach($category_types as $row)
                                            <option value="{{ $row->id }}" {{ old('category_type_id', $category->category_type_id) == $row->id ? 'selected' : '' }}>
                                                {{ $row->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_type_id')
                                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="sort_by" class="block text-sm font-medium text-slate-700 mb-1.5">Sort Order</label>
                                    <input type="number" name="sort_by" id="sort_by" value="{{ old('sort_by', $category->sort_by) }}"
                                           step="0.01" min="0" placeholder="0"
                                           class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors
                                                  @error('sort_by') border-red-400 bg-red-50 @enderror">
                                    @error('sort_by')
                                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Description --}}
                            <div>
                                <label for="description" class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                                <textarea name="description" id="description" rows="4"
                                          placeholder="Optional description for this category…"
                                          class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none transition-colors
                                                 @error('description') border-red-400 bg-red-50 @enderror">{{ old('description', $category->description) }}</textarea>
                                @error('description')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- ▌SECTION 2 · Stats (read-only) --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/60">
                            <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Category Stats</h2>
                                <p class="text-xs text-slate-400">Read-only overview</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                <div class="p-4 bg-slate-50 rounded-lg border border-slate-100 text-center">
                                    <p class="text-2xl font-bold text-slate-800">{{ $category->products_count ?? $category->products()->count() }}</p>
                                    <p class="text-xs text-slate-400 mt-1">Products linked</p>
                                </div>
                                <div class="p-4 bg-slate-50 rounded-lg border border-slate-100 text-center">
                                    <p class="text-2xl font-bold text-slate-800">{{ $category->sort_by ?? '—' }}</p>
                                    <p class="text-xs text-slate-400 mt-1">Sort position</p>
                                </div>
                                <div class="p-4 bg-slate-50 rounded-lg border border-slate-100 text-center col-span-2 sm:col-span-1">
                                    <p class="text-sm font-semibold text-slate-700 truncate">{{ $category->categoryType->name ?? '—' }}</p>
                                    <p class="text-xs text-slate-400 mt-1">Assigned type</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>{{-- end left column --}}

                {{-- ═══════════════════════════════════════════
                     RIGHT COLUMN / SIDEBAR (xl:col-span-4)
                ═══════════════════════════════════════════ --}}
                <div class="xl:col-span-4 space-y-5">

                    {{-- ▌SIDEBAR 1 · Record Info --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Record Info</h2>
                                <p class="text-xs text-slate-400">Timestamps</p>
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-medium">Created</span>
                                <span class="text-slate-700 font-mono">{{ $category->created_at?->format('d M Y') ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-medium">Last updated</span>
                                <span class="text-slate-700 font-mono">{{ $category->updated_at?->format('d M Y') ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-medium">Category ID</span>
                                <span class="text-slate-700 font-mono">#{{ $category->id }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- ▌SIDEBAR 2 · Actions --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Category
                        </button>
                        <a href="{{ route('categories.index') }}"
                           class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                            Cancel
                        </a>
                        <hr class="border-slate-100">
                        <button type="button" id="btn-delete-category"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-red-200 hover:bg-red-50 text-red-500 hover:text-red-700 text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete This Category
                        </button>
                    </div>

                </div>{{-- end sidebar --}}
            </div>{{-- end grid --}}
        </form>
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
                        <h3 class="text-base font-semibold text-slate-900">Delete Category</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            You're about to permanently delete <strong class="text-slate-800">{{ $category->name }}</strong>.
                            This cannot be undone.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button id="modal-cancel" class="flex-1 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg transition-colors">
                        Cancel
                    </button>
                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="flex-1">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors inline-flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete Category
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes modal-in { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
.animate-modal { animation:modal-in .2s ease-out both; }
</style>

@push('scripts')
<script>
$(function () {
    $('#btn-delete-category').on('click', () => { $('#delete-modal').removeClass('hidden'); $('body').css('overflow','hidden'); });
    $('#modal-cancel, #modal-backdrop').on('click', () => { $('#delete-modal').addClass('hidden'); $('body').css('overflow',''); });
    $(document).on('keydown', e => { if (e.key === 'Escape') { $('#delete-modal').addClass('hidden'); $('body').css('overflow',''); } });
});
</script>
@endpush
@endsection
