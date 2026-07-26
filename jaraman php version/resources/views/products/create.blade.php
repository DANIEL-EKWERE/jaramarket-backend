@extends('layouts.app')
@section('title', 'Add Product')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <a href="{{ route('products.index') }}" class="hover:text-emerald-600 transition-colors">Products</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">New Product</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Add New Product</h1>
            </div>
            <a href="{{ route('products.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Products
            </a>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6">

        {{-- Validation errors --}}
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

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="product-form">
            @csrf

            {{-- 2-column layout: left=main (8), right=sidebar (4) --}}
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
                                <p class="text-xs text-slate-400">Product name, description and categorisation</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-5">

                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Product Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}"
                                       placeholder="e.g. Jollof Rice Special"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors
                                              @error('name') border-red-400 bg-red-50 @else border-slate-300 bg-slate-50 @enderror">
                                @error('name')
                                    <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label for="description" class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                                    <textarea name="description" id="description" rows="4"
                                              placeholder="Briefly describe this product…"
                                              class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none transition-colors">{{ old('description') }}</textarea>
                                </div>
                                <div>
                                    <label for="preparation_steps" class="block text-sm font-medium text-slate-700 mb-1.5">Preparation Steps</label>
                                    <textarea name="preparation_steps" id="preparation_steps" rows="4"
                                              placeholder="Step 1: Wash the rice&#10;Step 2: Fry the tomatoes…"
                                              class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none transition-colors">{{ old('preparation_steps') }}</textarea>
                                    <p class="mt-1 text-xs text-slate-400">One step per line.</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    Categories <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                                    @foreach ($categories as $cat)
                                    <label for="cat-{{ $cat->id }}"
                                           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg border cursor-pointer select-none transition-colors
                                                  hover:border-emerald-400 hover:bg-emerald-50 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 border-slate-200">
                                        <input id="cat-{{ $cat->id }}" name="categories[]" type="checkbox"
                                               value="{{ $cat->id }}"
                                               {{ in_array($cat->id, old('categories', [])) ? 'checked' : '' }}
                                               class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500 flex-shrink-0">
                                        <span class="text-sm text-slate-700 font-medium leading-tight">{{ $cat->name }}</span>
                                    </label>
                                    @endforeach
                                </div>
                                @error('categories')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- ▌SECTION 2 · Ingredients --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Ingredients</h2>
                                    <p class="text-xs text-slate-400">Add ingredients to auto-calculate production cost</p>
                                </div>
                            </div>
                            <button type="button" id="btn-add-ingredient"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                Add Row
                            </button>
                        </div>
                        <div class="p-6">
                            {{-- Column headers --}}
                            <div class="hidden sm:grid grid-cols-12 gap-3 mb-2 px-1">
                                <div class="col-span-5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Ingredient</div>
                                <div class="col-span-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Quantity</div>
                                <div class="col-span-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Unit</div>
                                <div class="col-span-1"></div>
                            </div>
                            <div id="ingredients-container" class="space-y-2">
                                <div class="ingredient-item grid grid-cols-12 gap-2 sm:gap-3 items-center p-3 rounded-lg bg-slate-50 border border-slate-200">
                                    <div class="col-span-12 sm:col-span-5">
                                        <label class="block text-xs font-medium text-slate-500 mb-1 sm:hidden">Ingredient</label>
                                        <select name="ingredients[0][ingredient_id]" class="ingredient-select w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white">
                                            <option value="">— Select Ingredient —</option>
                                            @foreach($ingredients as $ing)
                                            <option value="{{ $ing->id }}" data-price="{{ $ing->price }}" data-unit="{{ $ing->unit }}">
                                                {{ $ing->name }} (₦{{ number_format($ing->price,2) }}/{{ $ing->unit }})
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-5 sm:col-span-3">
                                        <label class="block text-xs font-medium text-slate-500 mb-1 sm:hidden">Quantity</label>
                                        <input type="number" name="ingredients[0][quantity]" value="1" min="0.01" step="0.01"
                                               class="quantity-input w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-slate-50">
                                    </div>
                                    <div class="col-span-6 sm:col-span-3">
                                        <label class="block text-xs font-medium text-slate-500 mb-1 sm:hidden">Unit</label>
                                        <select name="ingredients[0][unit]" class="unit-select w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                                            @foreach($uoms as $uom)
                                            <option value="{{ $uom->code }}">{{ $uom->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-1 flex justify-end">
                                        <button type="button" class="remove-ingredient p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between p-3.5 bg-orange-50 border border-orange-100 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <span class="text-sm font-medium text-orange-800">Estimated Production Cost</span>
                                </div>
                                <span class="text-base font-bold text-orange-700">₦<span id="calculated_price">0.00</span></span>
                            </div>
                        </div>
                    </div>

                    {{-- ▌SECTION 3 · State-Specific Prices --}}
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
                                    <p class="text-xs text-slate-400">Override the default price for individual states/regions</p>
                                </div>
                            </div>
                            <button type="button" id="btn-add-state"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                Add State
                            </button>
                        </div>
                        <div class="p-6">
                            <div id="state-prices-container" class="space-y-3"></div>
                            <div id="state-prices-empty" class="flex flex-col items-center justify-center py-10 text-center">
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

                </div>{{-- end left column --}}

                {{-- ═══════════════════════════════════════════
                     RIGHT COLUMN / SIDEBAR (xl:col-span-4)
                ═══════════════════════════════════════════ --}}
                <div class="xl:col-span-4 space-y-5">

                    {{-- ▌SIDEBAR 1 · Product Image --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-pink-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Product Image</h2>
                                <p class="text-xs text-slate-400">JPG, PNG, WEBP — max 2 MB</p>
                            </div>
                        </div>
                        <div class="p-5">
                            <div id="img-preview-wrap" class="hidden mb-4 relative">
                                <img id="img-preview" src="#" alt="Preview"
                                     class="w-full h-44 rounded-lg object-cover border border-slate-200">
                                <button type="button" id="img-remove"
                                        class="absolute top-2 right-2 w-7 h-7 bg-white border border-slate-200 rounded-full flex items-center justify-center shadow-sm hover:bg-red-50 hover:border-red-300 transition-colors">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <label for="image_url"
                                   class="flex flex-col items-center justify-center gap-2 w-full py-8 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/50 transition-colors group">
                                <svg class="w-8 h-8 text-slate-300 group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <span class="text-sm font-medium text-slate-500 group-hover:text-emerald-600 transition-colors">Click to upload image</span>
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
                                <label for="price" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Selling Price (₦) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none"></span>
                                    <input type="number" name="price" id="price" value="{{ old('price') }}"
                                           step="0.01" min="0" placeholder="0.00"
                                           class="w-full pl-9 pr-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50 transition-colors
                                                  @error('price') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                </div>
                                @error('price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="discount_price" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Discount Price (₦) <span class="text-slate-400 text-xs font-normal">optional</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm pointer-events-none select-none"></span>
                                    <input type="number" name="discount_price" id="discount_price" value="{{ old('discount_price') }}"
                                           step="0.01" min="0" placeholder="0.00"
                                           class="w-full pl-9 pr-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                                </div>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                                <p class="text-xs text-slate-500 mb-1">Production cost (from ingredients)</p>
                                <p class="text-lg font-bold text-slate-800">₦<span id="calc_price_sidebar">0.00</span></p>
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
                                <p class="text-xs text-slate-400">Stock quantity and product rating</p>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label for="stock" class="block text-sm font-medium text-slate-700 mb-1.5">Stock Quantity</label>
                                <input type="number" name="stock" id="stock" value="{{ old('stock', 0) }}" min="0"
                                       class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                                @error('stock')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="rating" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Rating <span class="text-slate-400 text-xs font-normal">(0 – 5)</span>
                                </label>
                                <input type="number" name="rating" id="rating" value="{{ old('rating') }}"
                                       min="0" max="5" step="0.1" placeholder="4.5"
                                       class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                                @error('rating')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
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
                            Save Product
                        </button>
                        <a href="{{ route('products.index') }}"
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

@push('scripts')
<script>
const INGREDIENTS_DATA = {!! json_encode($ingredients->map(fn($i) => ['id'=>$i->id,'name'=>$i->name,'price'=>(float)$i->price,'unit'=>$i->unit])->values()) !!};
const UOMS_DATA        = {!! json_encode($uoms->map(fn($u) => ['code'=>$u->code,'name'=>$u->name])->values()) !!};
const STATES_DATA      = {!! json_encode($states->map(fn($s) => ['id'=>$s->id,'name'=>$s->name])->values()) !!};

$(function () {

    // ── Image preview ──────────────────────────────────────────────────────
    $('#image_url').on('change', function () {
        if (!this.files?.length) return;
        const reader = new FileReader();
        reader.onload = e => { $('#img-preview').attr('src', e.target.result); $('#img-preview-wrap').removeClass('hidden'); };
        reader.readAsDataURL(this.files[0]);
    });
    $('#img-remove').on('click', () => { $('#image_url').val(''); $('#img-preview-wrap').addClass('hidden'); });

    // ── Option builders ────────────────────────────────────────────────────
    function buildIngOptions(selId) {
        return '<option value="">— Select Ingredient —</option>' +
            INGREDIENTS_DATA.map(i => `<option value="${i.id}" data-price="${i.price}" data-unit="${i.unit}"${i.id==selId?' selected':''}>${i.name} (₦${i.price.toFixed(2)}/${i.unit})</option>`).join('');
    }
    function buildUomOptions(selCode) {
        return UOMS_DATA.map(u => `<option value="${u.code}"${u.code==selCode?' selected':''}>${u.name}</option>`).join('');
    }
    function buildStateOptions(selId) {
        return '<option value="">— Select State —</option>' +
            STATES_DATA.map(s => `<option value="${s.id}"${s.id==selId?' selected':''}>${s.name}</option>`).join('');
    }

    // ── Ingredient row builder ─────────────────────────────────────────────
    function makeIngRow(idx) {
        return `<div class="ingredient-item grid grid-cols-12 gap-2 sm:gap-3 items-center p-3 rounded-lg bg-slate-50 border border-slate-200">
            <div class="col-span-12 sm:col-span-5">
                <label class="block text-xs font-medium text-slate-500 mb-1 sm:hidden">Ingredient</label>
                <select name="ingredients[${idx}][ingredient_id]" class="ingredient-select w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">${buildIngOptions()}</select>
            </div>
            <div class="col-span-5 sm:col-span-3">
                <label class="block text-xs font-medium text-slate-500 mb-1 sm:hidden">Quantity</label>
                <input type="number" name="ingredients[${idx}][quantity]" value="1" min="0.01" step="0.01" class="quantity-input w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-slate-50">
            </div>
            <div class="col-span-6 sm:col-span-3">
                <label class="block text-xs font-medium text-slate-500 mb-1 sm:hidden">Unit</label>
                <select name="ingredients[${idx}][unit]" class="unit-select w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">${buildUomOptions()}</select>
            </div>
            <div class="col-span-1 flex justify-end">
                <button type="button" class="remove-ingredient p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>`;
    }

    // ── State price row builder ────────────────────────────────────────────
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
                        <input type="number" name="state_prices[${idx}][discount_price]" min="0" step="0.01" placeholder="0.00" class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
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

    let ingIdx = 1, stateIdx = 0;

    // ── Ingredient events ──────────────────────────────────────────────────
    $('#btn-add-ingredient').on('click', () => { $('#ingredients-container').append(makeIngRow(ingIdx++)); calcPrice(); });
    $(document).on('click', '.remove-ingredient', function () {
        if ($('.ingredient-item').length > 1) { $(this).closest('.ingredient-item').remove(); calcPrice(); }
    });

    // ── Cost calculation ───────────────────────────────────────────────────
    const CONV = { kg:{g:1000,piece:1},g:{kg:.001,piece:1},l:{ml:1000,cup:4,tbsp:66.67,tsp:200},ml:{l:.001,cup:.004,tbsp:.067,tsp:.2},cup:{l:.25,ml:250,tbsp:16,tsp:48},tbsp:{l:.015,ml:15,cup:.0625,tsp:3},tsp:{l:.005,ml:5,cup:.0208,tbsp:.333},piece:{kg:1,g:1},por:{kg:1,g:1} };
    function convert(q,f,t){ return f===t?q:(CONV[f]?.[t]?q*CONV[f][t]:q); }
    function calcPrice() {
        let total = 0;
        $('.ingredient-item').each(function() {
            const sel = $(this).find('.ingredient-select')[0];
            const opt = sel?.options[sel.selectedIndex];
            if (!opt?.value) return;
            total += convert(parseFloat($(this).find('.quantity-input').val())||0, $(this).find('.unit-select').val(), opt.dataset.unit) * (parseFloat(opt.dataset.price)||0);
        });
        const fmt = total.toFixed(2);
        $('#calculated_price').text(fmt);
        $('#calc_price_sidebar').text(fmt);
    }
    $(document).on('change input', '.ingredient-select,.quantity-input,.unit-select', calcPrice);

    // ── State price events ─────────────────────────────────────────────────
    function syncEmpty() { $('#state-prices-empty').toggleClass('hidden', $('#state-prices-container .state-price-item').length > 0); }
    $('#btn-add-state').on('click', () => { $('#state-prices-container').append(makeStateRow(stateIdx++)); syncEmpty(); });
    $(document).on('click', '.remove-state-price', function () { $(this).closest('.state-price-item').remove(); syncEmpty(); });

    // ── Form validation ────────────────────────────────────────────────────
    $('#product-form').on('submit', function(e) {
        if (!$('input[name="categories[]"]:checked').length) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: $('input[name="categories[]"]').first().offset().top - 150 }, 400);
            alert('Please select at least one category.');
        }
    });

    syncEmpty();
    calcPrice();
});
</script>
@endpush
