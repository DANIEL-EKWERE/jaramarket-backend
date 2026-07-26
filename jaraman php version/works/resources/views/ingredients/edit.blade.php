@extends('layouts.app')
@section('title', 'Edit Ingredient')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <a href="{{ route('ingredients.index') }}" class="hover:text-emerald-600 transition-colors">Ingredients</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium truncate max-w-xs">{{ $ingredient->name }}</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Edit Ingredient</h1>
            </div>
            <a href="{{ route('ingredients.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Ingredients
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

        <form action="{{ route('ingredients.update', $ingredient) }}" method="POST" enctype="multipart/form-data" id="ingredient-form">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

                {{-- ═══════════════════════════════════════════
                     LEFT COLUMN (xl:col-span-8)
                ═══════════════════════════════════════════ --}}
                <div class="xl:col-span-8 space-y-5">

                    {{-- ▌SECTION 1 · Basic Information --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/60">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Basic Information</h2>
                                <p class="text-xs text-slate-400">Ingredient name, category and unit of measure</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-5">

                            {{-- Name --}}
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Ingredient Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name', $ingredient->name) }}"
                                       placeholder="e.g. Fresh Tomatoes"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors
                                              @error('name') border-red-400 bg-red-50 @else border-slate-300 bg-slate-50 @enderror">
                                @error('name')
                                    <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- Category + Unit --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1.5">Category</label>
                                    <select name="category_id" id="category_id"
                                            class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors
                                                   @error('category_id') border-red-400 bg-red-50 @enderror">
                                        <option value="">— Select Category —</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ old('category_id', $ingredient->category_id) == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="unit" class="block text-sm font-medium text-slate-700 mb-1.5">
                                        Unit of Measure <span class="text-red-500">*</span>
                                    </label>
                                    <select name="unit" id="unit"
                                            class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors
                                                   @error('unit') border-red-400 bg-red-50 @enderror">
                                        <option value="">— Select Unit —</option>
                                        @foreach($units as $u)
                                            <option value="{{ $u->code }}" {{ old('unit', $ingredient->unit) == $u->code ? 'selected' : '' }}>
                                                {{ $u->name }} ({{ $u->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unit')
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
                                <textarea name="description" id="description" rows="3"
                                          placeholder="Optional notes about this ingredient…"
                                          class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none transition-colors">{{ old('description', $ingredient->description) }}</textarea>
                                @error('description')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                        </div>
                    </div>

                    {{-- ▌SECTION 2 · State-Specific Prices --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">State-Specific Prices</h2>
                                    <p class="text-xs text-slate-400">Override the default price for individual states / regions</p>
                                </div>
                            </div>
                            <button type="button" id="btn-add-state"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                Add State
                            </button>
                        </div>
                        <div class="p-6">
                            <div id="state-prices-container" class="space-y-3">
                                @foreach ($ingredient->statePrices as $si => $sp)
                                <div class="state-price-item rounded-xl border border-blue-200 bg-blue-50/60 p-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-11 gap-3">
                                        <div class="sm:col-span-4">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">State <span class="text-red-400">*</span></label>
                                            <select name="state_prices[{{ $si }}][state_id]" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                                <option value="">— Select State —</option>
                                                @foreach($states as $st)
                                                <option value="{{ $st->id }}" {{ $st->id == $sp->state_id ? 'selected' : '' }}>{{ $st->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Price (₦) <span class="text-red-400">*</span></label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none"></span>
                                                <input type="number" name="state_prices[{{ $si }}][price]" value="{{ $sp->price }}" min="0" step="0.01" placeholder="0.00"
                                                       class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                            </div>
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Discount <span class="text-slate-400 font-normal">(opt.)</span></label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none"></span>
                                                <input type="number" name="state_prices[{{ $si }}][discounted_price]" value="{{ $sp->discounted_price }}" min="0" step="0.01" placeholder="0.00"
                                                       class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1 flex sm:items-end sm:justify-center">
                                            <button type="button" class="remove-state-price inline-flex items-center gap-1 px-3 py-2.5 sm:p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors text-xs">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span class="sm:hidden">Remove</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div id="state-prices-empty" class="{{ $ingredient->statePrices->count() > 0 ? 'hidden' : '' }} flex flex-col items-center justify-center py-10 text-center">
                                <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                                    <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-slate-500">No state overrides added</p>
                                <p class="text-xs text-slate-400 mt-1">Default price will apply to all states</p>
                            </div>
                        </div>
                    </div>


                    {{-- ▌LGA-Specific Prices --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mt-5">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">LGA-Specific Prices</h2>
                                    <p class="text-xs text-slate-400">Override price for a specific Local Government Area (highest priority)</p>
                                </div>
                            </div>
                            <button type="button" id="btn-add-lga"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                Add LGA
                            </button>
                        </div>
                        <div class="p-6">
                            <div id="lga-prices-container" class="space-y-3">
                                @foreach ($ingredient->lgaPrices as $li => $lp)
                                <div class="lga-price-item rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-11 gap-3">
                                        <div class="sm:col-span-4">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">LGA <span class="text-red-400">*</span></label>
                                            <select name="lga_prices[{{ $li }}][lga_id]" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                                <option value="">— Select LGA —</option>
                                                @foreach($lgas as $lg)
                                                <option value="{{ $lg->id }}" {{ $lg->id == $lp->lga_id ? 'selected' : '' }}>
                                                    {{ $lg->name }} ({{ $lg->state->name ?? '' }})
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Price (₦) <span class="text-red-400">*</span></label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none"></span>
                                                <input type="number" name="lga_prices[{{ $li }}][price]" value="{{ $lp->price }}" min="0" step="0.01" placeholder="0.00"
                                                       class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                            </div>
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Discount <span class="text-slate-400 font-normal">(opt.)</span></label>
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none"></span>
                                                <input type="number" name="lga_prices[{{ $li }}][discounted_price]" value="{{ $lp->discounted_price }}" min="0" step="0.01" placeholder="0.00"
                                                       class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1 flex sm:items-end sm:justify-center">
                                            <button type="button" class="remove-lga-price inline-flex items-center gap-1 px-3 py-2.5 sm:p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors text-xs">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span class="sm:hidden">Remove</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div id="lga-prices-empty" class="{{ $ingredient->lgaPrices->count() > 0 ? 'hidden' : '' }} flex flex-col items-center justify-center py-10 text-center">
                                <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                                    <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-slate-500">No LGA overrides added</p>
                                <p class="text-xs text-slate-400 mt-1">State or default price will apply</p>
                            </div>
                        </div>
                    </div>

                </div>{{-- end left column --}}

                {{-- ═══════════════════════════════════════════
                     RIGHT COLUMN / SIDEBAR (xl:col-span-4)
                ═══════════════════════════════════════════ --}}
                <div class="xl:col-span-4 space-y-5">

                    {{-- ▌SIDEBAR 1 · Image --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-pink-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Ingredient Image</h2>
                                <p class="text-xs text-slate-400">JPG, PNG, WEBP — max 2 MB</p>
                            </div>
                        </div>
                        <div class="p-5">
                            <div id="img-preview-wrap" class="{{ $ingredient->image_url ? '' : 'hidden' }} mb-4 relative">
                                <img id="img-preview"
                                     src="{{ $ingredient->image_url ? get_media_url($ingredient->image_url) : '#' }}"
                                     alt="Preview"
                                     class="w-full h-44 rounded-lg object-cover border border-slate-200">
                                <button type="button" id="img-remove"
                                        class="absolute top-2 right-2 w-7 h-7 bg-white border border-slate-200 rounded-full flex items-center justify-center shadow-sm hover:bg-red-50 hover:border-red-300 transition-colors">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <label for="image_url"
                                   class="flex flex-col items-center justify-center gap-2 w-full py-7 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/50 transition-colors group">
                                <svg class="w-7 h-7 text-slate-300 group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <span class="text-sm font-medium text-slate-500 group-hover:text-emerald-600 transition-colors">
                                    {{ $ingredient->image_url ? 'Replace image' : 'Upload image' }}
                                </span>
                                <span class="text-xs text-slate-400">or drag and drop</span>
                                <input type="file" name="image_url" id="image_url" accept="image/*" class="hidden">
                            </label>
                            @error('image_url')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- ▌SIDEBAR 2 · Pricing --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Pricing</h2>
                                <p class="text-xs text-slate-400">Default selling prices</p>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label for="default_price" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Default Price (₦) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none"></span>
                                    <input type="number" name="price" id="default_price" value="{{ old('default_price', $ingredient->price) }}"
                                           step="0.01" min="0" placeholder="0.00"
                                           class="w-full pl-9 pr-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50 transition-colors
                                                  @error('default_price') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                </div>
                                @error('price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="discounted_price" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Discounted Price (₦) <span class="text-slate-400 text-xs font-normal">optional</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none"></span>
                                    <input type="number" name="discounted_price" id="discounted_price" value="{{ old('discounted_price', $ingredient->discounted_price) }}"
                                           step="0.01" min="0" placeholder="0.00"
                                           class="w-full pl-9 pr-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                                </div>
                                @error('discounted_price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- ▌SIDEBAR 3 · Inventory --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Inventory</h2>
                                <p class="text-xs text-slate-400">Current stock quantity</p>
                            </div>
                        </div>
                        <div class="p-5">
                            <div>
                                <label for="stock" class="block text-sm font-medium text-slate-700 mb-1.5">Stock Quantity</label>
                                <input type="number" name="stock" id="stock" value="{{ old('stock', $ingredient->stock) }}" min="0"
                                       class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                                @error('stock')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- ▌SIDEBAR 4 · Actions --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Ingredient
                        </button>
                        <a href="{{ route('ingredients.index') }}"
                           class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                            Cancel
                        </a>
                        <hr class="border-slate-100">
                        <button type="button" id="btn-delete-ingredient"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-red-200 hover:bg-red-50 text-red-500 hover:text-red-700 text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete This Ingredient
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
                        <h3 class="text-base font-semibold text-slate-900">Delete Ingredient</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            You're about to permanently delete <strong class="text-slate-800">{{ $ingredient->name }}</strong>.
                            This cannot be undone.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button id="modal-cancel" class="flex-1 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg transition-colors">
                        Cancel
                    </button>
                    <form action="{{ route('ingredients.destroy', $ingredient) }}" method="POST" class="flex-1">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors inline-flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete Ingredient
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

@endsection

@push('scripts')
<script>
const STATES_DATA   = {!! json_encode($states->map(fn($s) => ['id'=>$s->id,'name'=>$s->name])->values()) !!};
const LGAS_DATA     = {!! json_encode($lgas->map(fn($l) => ['id'=>$l->id,'name'=>$l->name,'state'=>$l->state?->name])->values()) !!};
let stateIdx        = {{ $ingredient->statePrices->count() }};
let lgaIdx          = {{ $ingredient->lgaPrices->count() }};

$(function () {

    // ── Image preview ──────────────────────────────────────────────────────
    $('#image_url').on('change', function () {
        if (!this.files?.length) return;
        const reader = new FileReader();
        reader.onload = e => { $('#img-preview').attr('src', e.target.result); $('#img-preview-wrap').removeClass('hidden'); };
        reader.readAsDataURL(this.files[0]);
    });
    $('#img-remove').on('click', () => { $('#image_url').val(''); $('#img-preview-wrap').addClass('hidden'); });

    // ── State price row builder ────────────────────────────────────────────
    function buildStateOptions(selId) {
        return '<option value="">— Select State —</option>' +
            STATES_DATA.map(s => `<option value="${s.id}"${s.id==selId?' selected':''}>${s.name}</option>`).join('');
    }

    function makeStateRow(idx) {
        return `<div class="state-price-item rounded-xl border border-blue-200 bg-blue-50/60 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-11 gap-3">
                <div class="sm:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">State <span class="text-red-400">*</span></label>
                    <select name="state_prices[${idx}][state_id]" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">${buildStateOptions()}</select>
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Price (₦) <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none">₦</span>
                        <input type="number" name="state_prices[${idx}][price]" min="0" step="0.01" placeholder="0.00" class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Discount <span class="text-slate-400 font-normal">(opt.)</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none">₦</span>
                        <input type="number" name="state_prices[${idx}][discounted_price]" min="0" step="0.01" placeholder="0.00" class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    </div>
                </div>
                <div class="sm:col-span-1 flex sm:items-end sm:justify-center">
                    <button type="button" class="remove-state-price inline-flex items-center gap-1 px-3 py-2.5 sm:p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span class="sm:hidden">Remove</span>
                    </button>
                </div>
            </div>
        </div>`;
    }

    function syncEmpty() { $('#state-prices-empty').toggleClass('hidden', $('#state-prices-container .state-price-item').length > 0); }
    $('#btn-add-state').on('click', () => { $('#state-prices-container').append(makeStateRow(stateIdx++)); syncEmpty(); });
    $(document).on('click', '.remove-state-price', function () { $(this).closest('.state-price-item').remove(); syncEmpty(); });

    // ── LGA price row builder ──────────────────────────────────────────────
    function buildLgaOptions(selId) {
        return '<option value="">— Select LGA —</option>' +
            LGAS_DATA.map(l => `<option value="${l.id}"${l.id==selId?' selected':''}>${l.name}${l.state?' ('+l.state+')':''}</option>`).join('');
    }

    function makeLgaRow(idx) {
        return `<div class="lga-price-item rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-11 gap-3">
                <div class="sm:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">LGA <span class="text-red-400">*</span></label>
                    <select name="lga_prices[${idx}][lga_id]" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">${buildLgaOptions()}</select>
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Price (₦) <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none">₦</span>
                        <input type="number" name="lga_prices[${idx}][price]" min="0" step="0.01" placeholder="0.00" class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Discount <span class="text-slate-400 font-normal">(opt.)</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none">₦</span>
                        <input type="number" name="lga_prices[${idx}][discounted_price]" min="0" step="0.01" placeholder="0.00" class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                    </div>
                </div>
                <div class="sm:col-span-1 flex sm:items-end sm:justify-center">
                    <button type="button" class="remove-lga-price inline-flex items-center gap-1 px-3 py-2.5 sm:p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span class="sm:hidden">Remove</span>
                    </button>
                </div>
            </div>
        </div>`;
    }

    function syncLgaEmpty() { $('#lga-prices-empty').toggleClass('hidden', $('#lga-prices-container .lga-price-item').length > 0); }
    $('#btn-add-lga').on('click', () => { $('#lga-prices-container').append(makeLgaRow(lgaIdx++)); syncLgaEmpty(); });
    $(document).on('click', '.remove-lga-price', function () { $(this).closest('.lga-price-item').remove(); syncLgaEmpty(); });

    // ── Delete modal ───────────────────────────────────────────────────────
    $('#btn-delete-ingredient').on('click', () => { $('#delete-modal').removeClass('hidden'); $('body').css('overflow','hidden'); });
    $('#modal-cancel, #modal-backdrop').on('click', () => { $('#delete-modal').addClass('hidden'); $('body').css('overflow',''); });
    $(document).on('keydown', e => { if (e.key === 'Escape') { $('#delete-modal').addClass('hidden'); $('body').css('overflow',''); } });

    syncEmpty();
    syncLgaEmpty();
});
</script>
@endpush
