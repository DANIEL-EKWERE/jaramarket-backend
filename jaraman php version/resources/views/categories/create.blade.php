@extends('layouts.app')
@section('title', 'Add Category')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <a href="{{ route('categories.index') }}" class="hover:text-emerald-600 transition-colors">Categories</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">New Category</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Add New Category</h1>
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

        @if ($errors->any())
        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-800 mb-1">Please fix {{ $errors->count() }} error(s):</p>
                    <ul class="text-sm text-red-700 space-y-0.5 list-disc list-inside">
                        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('categories.store') }}" method="POST" id="category-form">
            @csrf

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
                                <input type="text" name="name" id="name" value="{{ old('name') }}"
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
                                            <option value="{{ $row->id }}" {{ old('category_type_id') == $row->id ? 'selected' : '' }}>
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
                                    <input type="number" name="sort_by" id="sort_by" value="{{ old('sort_by') }}"
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
                                                 @error('description') border-red-400 bg-red-50 @enderror">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>

                </div>{{-- end left column --}}

                {{-- ═══════════════════════════════════════════
                     RIGHT COLUMN / SIDEBAR (xl:col-span-4)
                ═══════════════════════════════════════════ --}}
                <div class="xl:col-span-4 space-y-5">

                    {{-- ▌SIDEBAR 1 · Tips --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Tips</h2>
                                <p class="text-xs text-slate-400">Helpful guidance</p>
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex items-start gap-2.5">
                                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                                <p class="text-xs text-slate-500 leading-relaxed">Use a clear, concise name that customers will immediately recognise.</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                                <p class="text-xs text-slate-500 leading-relaxed">Sort order controls the display sequence — lower numbers appear first.</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                                <p class="text-xs text-slate-500 leading-relaxed">Assigning a type groups related categories for better navigation.</p>
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
                            Save Category
                        </button>
                        <a href="{{ route('categories.index') }}"
                           class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                            Cancel
                        </a>
                    </div>

                </div>{{-- end sidebar --}}
            </div>{{-- end grid --}}
        </form>
    </div>
</div>
@endsection
