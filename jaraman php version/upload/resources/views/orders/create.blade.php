@extends('layouts.app')
@section('title', 'Create Order')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <a href="{{ route('orders.index') }}" class="hover:text-emerald-600 transition-colors">Orders</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">New Order</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Create New Order</h1>
            </div>
            <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6">
        @if($errors->any())
        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <div>
                    <p class="text-sm font-semibold text-red-800 mb-1">Please fix {{ $errors->count() }} error(s):</p>
                    <ul class="text-sm text-red-700 list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('orders.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="xl:col-span-8 space-y-5">

                    {{-- Customer --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div><h2 class="text-sm font-semibold text-slate-800">Customer</h2><p class="text-xs text-slate-400">Select the customer for this order</p></div>
                        </div>
                        <div class="p-6">
                            <label for="user_id" class="block text-sm font-medium text-slate-700 mb-1.5">Customer <span class="text-red-500">*</span></label>
                            <select name="user_id" id="user_id" required class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('user_id') border-red-400 @enderror">
                                <option value="">— Select Customer —</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id')==$user->id?'selected':'' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('user_id')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Order Items --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <div><h2 class="text-sm font-semibold text-slate-800">Order Items</h2><p class="text-xs text-slate-400">Add products to this order</p></div>
                            </div>
                        </div>
                        <div class="p-6 space-y-4">
                            {{-- Column Headers --}}
                            <div class="grid grid-cols-12 gap-3 text-xs font-semibold text-slate-400 uppercase tracking-wider px-1">
                                <div class="col-span-7">Product</div>
                                <div class="col-span-3">Quantity</div>
                                <div class="col-span-2"></div>
                            </div>
                            <div id="order-items" class="space-y-3">
                                @for($i=0;$i<3;$i++)
                                <div class="order-item grid grid-cols-12 gap-3 items-center">
                                    <div class="col-span-7">
                                        <select name="items[{{ $i }}][product_id]" class="w-full px-3 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" {{ $i==0?'required':'' }}>
                                            <option value="">— Select Product —</option>
                                            @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ old("items.$i.product_id")==$product->id?'selected':'' }}>{{ $product->name }} — ₦{{ number_format($product->price,2) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-3">
                                        <input type="number" name="items[{{ $i }}][quantity]" min="1" value="{{ old("items.$i.quantity",1) }}" class="w-full px-3 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono" {{ $i==0?'required':'' }}>
                                    </div>
                                    <div class="col-span-2 flex justify-end">
                                        @if($i>0)
                                        <button type="button" onclick="this.closest('.order-item').remove()" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                    {{-- Meal Prep --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <div><h2 class="text-sm font-semibold text-slate-800">Meal Preparation Notes</h2><p class="text-xs text-slate-400">Optional instructions for this order</p></div>
                        </div>
                        <div class="p-6">
                            <textarea name="meal_prep" rows="4" placeholder="Enter any specific instructions for meal preparation (optional)…" class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none">{{ old('meal_prep') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- SIDEBAR --}}
                <div class="xl:col-span-4 space-y-5">
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div><h2 class="text-sm font-semibold text-slate-800">Order Total</h2><p class="text-xs text-slate-400">Enter the confirmed amount</p></div>
                        </div>
                        <div class="p-5">
                            <label for="total" class="block text-sm font-medium text-slate-700 mb-1.5">Total Amount (₦) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3.5 flex items-center text-slate-400 text-sm font-medium pointer-events-none">₦</span>
                                <input type="number" step="0.01" name="total" id="total" required value="{{ old('total') }}" class="w-full pl-8 pr-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('total') border-red-400 @enderror">
                            </div>
                            @error('total')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Create Order
                        </button>
                        <a href="{{ route('orders.index') }}" class="w-full inline-flex items-center justify-center px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
