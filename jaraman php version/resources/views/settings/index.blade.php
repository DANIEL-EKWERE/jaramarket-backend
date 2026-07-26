@extends('layouts.app')
@section('title', 'System Settings')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="text-slate-600 font-medium">System Settings</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Website Settings</h1>
            </div>
            <p class="text-xs text-slate-400 hidden sm:block">Changes are saved per section</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mx-6 lg:mx-8 xl:mx-10 mt-5">
        <div class="flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    </div>
    @endif

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- LEFT NAV TABS --}}
            <div class="xl:col-span-3">
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden sticky top-24">
                    <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/60">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Configuration</p>
                    </div>
                    <nav class="p-2 space-y-1" id="settings-nav">
                        <button data-tab="general"
                                class="settings-tab w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-left transition-colors active-tab">
                            <div class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 tab-icon bg-emerald-100">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            General
                        </button>
                        <button data-tab="contact"
                                class="settings-tab w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-left transition-colors text-slate-600 hover:bg-slate-50">
                            <div class="w-7 h-7 rounded-md bg-slate-100 flex items-center justify-center flex-shrink-0 tab-icon">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            Contact Information
                        </button>
                        <button data-tab="payment"
                                class="settings-tab w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-left transition-colors text-slate-600 hover:bg-slate-50">
                            <div class="w-7 h-7 rounded-md bg-slate-100 flex items-center justify-center flex-shrink-0 tab-icon">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                            Payment & Shipping
                        </button>
                        <button data-tab="social"
                                class="settings-tab w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-left transition-colors text-slate-600 hover:bg-slate-50">
                            <div class="w-7 h-7 rounded-md bg-slate-100 flex items-center justify-center flex-shrink-0 tab-icon">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                            </div>
                            Social Media
                        </button>
                        <button data-tab="commission"
                                class="settings-tab w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-left transition-colors text-slate-600 hover:bg-slate-50">
                            <div class="w-7 h-7 rounded-md bg-slate-100 flex items-center justify-center flex-shrink-0 tab-icon">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            Commissions & Bonus
                        </button>
                    </nav>
                </div>
            </div>

            {{-- RIGHT CONTENT --}}
            <div class="xl:col-span-9">
                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- ══ GENERAL ══ --}}
                    <div id="tab-general" class="tab-panel space-y-5">
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">General Settings</h2>
                                    <p class="text-xs text-slate-400">Site identity, branding and regional preferences</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-5">

                                <div>
                                    <label for="site_name" class="block text-sm font-medium text-slate-700 mb-1.5">Site Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="site_name" id="site_name"
                                           value="{{ old('site_name', $settings['site_name'] ?? 'JaraMarket') }}" required
                                           class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('site_name') border-red-400 bg-red-50 @enderror">
                                    @error('site_name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="site_description" class="block text-sm font-medium text-slate-700 mb-1.5">Site Description</label>
                                    <textarea name="site_description" id="site_description" rows="3"
                                              class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none transition-colors">{{ old('site_description', $settings['site_description'] ?? '') }}</textarea>
                                    <p class="mt-1 text-xs text-slate-400">Brief description used in meta tags and SEO.</p>
                                    @error('site_description')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                    {{-- Company Logo --}}
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Company Logo</label>
                                        <div class="flex items-center gap-4">
                                            <span class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center flex-shrink-0">
                                                @php $logoUrl = $settings['company_logo'] ?? null; @endphp
                                                <img id="logoPreview" src="{{ $logoUrl ? get_media_url($logoUrl) : 'https://via.placeholder.com/64?text=Logo' }}"
                                                     alt="Logo" class="h-full w-full object-cover">
                                            </span>
                                            <div>
                                                <input id="company_logo" name="company_logo" type="file" accept="image/*"
                                                       class="text-sm text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                                                       onchange="previewLogo(event)">
                                                <p class="text-xs text-slate-400 mt-1">PNG, JPG — recommended 200×200px</p>
                                            </div>
                                        </div>
                                        @error('company_logo')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>

                                    {{-- Favicon --}}
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Favicon</label>
                                        <div class="flex items-center gap-4">
                                            <span class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center flex-shrink-0">
                                                @php $favUrl = $settings['favicon_logo'] ?? null; @endphp
                                                <img id="favPreview" src="{{ $favUrl ? get_media_url($favUrl) : 'https://via.placeholder.com/64?text=Fav' }}"
                                                     alt="Favicon" class="h-full w-full object-cover">
                                            </span>
                                            <div>
                                                <input id="favicon_logo" name="favicon_logo" type="file" accept="image/*"
                                                       class="text-sm text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-600 hover:file:bg-slate-200"
                                                       onchange="previewFav(event)">
                                                <p class="text-xs text-slate-400 mt-1">ICO, PNG — recommended 32×32px</p>
                                            </div>
                                        </div>
                                        @error('favicon_logo')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>

                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label for="timezone" class="block text-sm font-medium text-slate-700 mb-1.5">Timezone</label>
                                        <select name="timezone" id="timezone"
                                                class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            @php $timezones = ['UTC'=>'UTC','America/New_York'=>'Eastern (US)','America/Chicago'=>'Central (US)','America/Los_Angeles'=>'Pacific (US)','Europe/London'=>'London','Africa/Lagos'=>'West Africa Time (Nigeria)','Asia/Tokyo'=>'Tokyo','Australia/Sydney'=>'Sydney']; @endphp
                                            @foreach($timezones as $val => $label)
                                            <option value="{{ $val }}" {{ old('timezone', $settings['timezone'] ?? 'UTC') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="date_format" class="block text-sm font-medium text-slate-700 mb-1.5">Date Format</label>
                                        <select name="date_format" id="date_format"
                                                class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            @php $now = new DateTime(); $formats = ['Y-m-d'=>$now->format('Y-m-d'),'m/d/Y'=>$now->format('m/d/Y'),'d/m/Y'=>$now->format('d/m/Y'),'F j, Y'=>$now->format('F j, Y'),'j F, Y'=>$now->format('j F, Y')]; @endphp
                                            @foreach($formats as $val => $label)
                                            <option value="{{ $val }}" {{ old('date_format', $settings['date_format'] ?? 'Y-m-d') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Save Settings
                            </button>
                        </div>
                    </div>

                    {{-- ══ CONTACT ══ --}}
                    <div id="tab-contact" class="tab-panel hidden space-y-5">
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Contact Information</h2>
                                    <p class="text-xs text-slate-400">Public-facing business contact details</p>
                                </div>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                                <div>
                                    <label for="contact_email" class="block text-sm font-medium text-slate-700 mb-1.5">Contact Email <span class="text-red-500">*</span></label>
                                    <input type="email" name="contact_email" id="contact_email"
                                           value="{{ old('contact_email', $settings['contact_email'] ?? '') }}" required
                                           class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('contact_email') border-red-400 bg-red-50 @enderror">
                                    @error('contact_email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="contact_phone" class="block text-sm font-medium text-slate-700 mb-1.5">Contact Phone</label>
                                    <input type="text" name="contact_phone" id="contact_phone"
                                           value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}"
                                           class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>

                                <div class="md:col-span-2">
                                    <label for="support_email" class="block text-sm font-medium text-slate-700 mb-1.5">Support Email</label>
                                    <input type="email" name="support_email" id="support_email"
                                           value="{{ old('support_email', $settings['support_email'] ?? '') }}"
                                           class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>

                                <div class="md:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-slate-700 mb-1.5">Business Address</label>
                                    <textarea name="address" id="address" rows="3"
                                              class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none">{{ old('address', $settings['address'] ?? '') }}</textarea>
                                </div>

                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Save Settings
                            </button>
                        </div>
                    </div>

                    {{-- ══ PAYMENT ══ --}}
                    <div id="tab-payment" class="tab-panel hidden space-y-5">
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Payment & Shipping</h2>
                                    <p class="text-xs text-slate-400">Currency, tax, shipping fees and payment methods</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-5">

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <label for="currency" class="block text-sm font-medium text-slate-700 mb-1.5">Currency <span class="text-red-500">*</span></label>
                                        <select name="currency" id="currency" required
                                                class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            @php $currencies = ['₦'=>'Nigerian Naira (₦)','$'=>'US Dollar ($)','€'=>'Euro (€)','£'=>'British Pound (£)','₹'=>'Indian Rupee (₹)']; @endphp
                                            @foreach($currencies as $val => $label)
                                            <option value="{{ $val }}" {{ old('currency', $settings['currency'] ?? '₦') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('currency')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="tax_rate" class="block text-sm font-medium text-slate-700 mb-1.5">Tax Rate (%) <span class="text-red-500">*</span></label>
                                        <input type="number" name="tax_rate" id="tax_rate"
                                               value="{{ old('tax_rate', $settings['tax_rate'] ?? '7.5') }}" step="0.01" min="0" max="100" required
                                               class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('tax_rate') border-red-400 bg-red-50 @enderror">
                                        @error('tax_rate')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="shipping_fee" class="block text-sm font-medium text-slate-700 mb-1.5">Default Shipping Fee <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-3.5 flex items-center text-slate-400 text-sm font-semibold pointer-events-none">{{ $settings['currency'] ?? '₦' }}</span>
                                            <input type="number" name="shipping_fee" id="shipping_fee"
                                                   value="{{ old('shipping_fee', $settings['shipping_fee'] ?? '0') }}" step="0.01" min="0" required
                                                   class="w-full pl-9 pr-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('shipping_fee') border-red-400 bg-red-50 @enderror">
                                        </div>
                                        @error('shipping_fee')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Accepted Payment Methods</label>
                                        <div class="space-y-2">
                                            @php
                                                $paymentMethods = ['wallet'=>'Wallet Payment','credit_card'=>'Credit Card','paypal'=>'PayPal','bank_transfer'=>'Bank Transfer','cash_on_delivery'=>'Cash on Delivery'];
                                                $savedMethods = old('payment_methods', $settings['payment_methods'] ?? 'credit_card');
                                                $savedMethodsArray = is_array($savedMethods) ? $savedMethods : explode(',', $savedMethods);
                                            @endphp
                                            @foreach($paymentMethods as $val => $label)
                                            <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg border border-slate-200 cursor-pointer hover:border-emerald-300 has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50 transition-colors">
                                                <input type="checkbox" name="payment_methods[]" value="{{ $val }}"
                                                       {{ in_array($val, $savedMethodsArray) ? 'checked' : '' }}
                                                       class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500">
                                                <span class="text-sm text-slate-700">{{ $label }}</span>
                                            </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <label for="order_statuses" class="block text-sm font-medium text-slate-700 mb-1.5">Available Order Statuses</label>
                                        <textarea name="order_statuses" id="order_statuses" rows="7"
                                                  placeholder="Enter each status on a new line"
                                                  class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none font-mono">{{ old('order_statuses', $settings['order_statuses'] ?? "pending\nprocessing\nshipped\ndelivered\ncancelled") }}</textarea>
                                        <p class="mt-1 text-xs text-slate-400">One status per line.</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Save Settings
                            </button>
                        </div>
                    </div>

                    {{-- ══ SOCIAL ══ --}}
                    <div id="tab-social" class="tab-panel hidden space-y-5">
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Social Media</h2>
                                    <p class="text-xs text-slate-400">Platform links shown on the website</p>
                                </div>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                                @php
                                    $socials = [
                                        'social_facebook'  => ['label' => 'Facebook URL',  'placeholder' => 'https://facebook.com/…'],
                                        'social_twitter'   => ['label' => 'Twitter / X URL','placeholder' => 'https://twitter.com/…'],
                                        'social_instagram' => ['label' => 'Instagram URL',  'placeholder' => 'https://instagram.com/…'],
                                        'social_youtube'   => ['label' => 'YouTube URL',    'placeholder' => 'https://youtube.com/…'],
                                        'social_tiktok'    => ['label' => 'TikTok URL',     'placeholder' => 'https://tiktok.com/@…'],
                                    ];
                                @endphp
                                @foreach($socials as $key => $s)
                                <div>
                                    <label for="{{ $key }}" class="block text-sm font-medium text-slate-700 mb-1.5">{{ $s['label'] }}</label>
                                    <input type="url" name="{{ $key }}" id="{{ $key }}"
                                           value="{{ old($key, $settings[$key] ?? '') }}"
                                           placeholder="{{ $s['placeholder'] }}"
                                           class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error($key) border-red-400 bg-red-50 @enderror">
                                    @error($key)<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Save Settings
                            </button>
                        </div>
                    </div>

                    {{-- ══ COMMISSION ══ --}}
                    <div id="tab-commission" class="tab-panel hidden space-y-5">
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Commissions & Bonus</h2>
                                    <p class="text-xs text-slate-400">Referral bonuses and minimum order thresholds</p>
                                </div>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">

                                <div>
                                    <label for="minimum_order_amount" class="block text-sm font-medium text-slate-700 mb-1.5">Minimum Order Amount</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-3.5 flex items-center text-slate-400 text-sm font-semibold pointer-events-none">{{ $settings['currency'] ?? '₦' }}</span>
                                        <input type="number" name="minimum_order_amount" id="minimum_order_amount"
                                               value="{{ old('minimum_order_amount', $settings['minimum_order_amount'] ?? '20000') }}" min="0"
                                               class="w-full pl-9 pr-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('minimum_order_amount') border-red-400 @enderror">
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">Orders below this amount are not eligible for commission.</p>
                                    @error('minimum_order_amount')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="first_order_bonus" class="block text-sm font-medium text-slate-700 mb-1.5">First Order Referral Bonus (%)</label>
                                    <div class="relative">
                                        <input type="number" name="first_order_bonus" id="first_order_bonus"
                                               value="{{ old('first_order_bonus', $settings['first_order_bonus'] ?? '20') }}" min="0" max="100" step="0.1"
                                               class="w-full px-4 pr-9 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        <span class="absolute inset-y-0 right-3.5 flex items-center text-slate-400 text-sm pointer-events-none">%</span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">Paid on first order from a referred customer.</p>
                                    @error('first_order_bonus')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="repeat_order_bonus" class="block text-sm font-medium text-slate-700 mb-1.5">Repeat Order Referral Bonus (%)</label>
                                    <div class="relative">
                                        <input type="number" name="repeat_order_bonus" id="repeat_order_bonus"
                                               value="{{ old('repeat_order_bonus', $settings['repeat_order_bonus'] ?? '10') }}" min="0" max="100" step="0.1"
                                               class="w-full px-4 pr-9 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        <span class="absolute inset-y-0 right-3.5 flex items-center text-slate-400 text-sm pointer-events-none">%</span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">Paid on every subsequent order from a referred customer.</p>
                                    @error('repeat_order_bonus')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>

                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Save Settings
                            </button>
                        </div>
                    </div>

                
                    {{-- ▌Storage Settings --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">File Storage</h2>
                                <p class="text-xs text-slate-400">Choose where uploaded files are stored</p>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Storage Driver</label>
                                <select name="storage_disk" id="storageDisk"
                                        class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="public" {{ ($settings['storage_disk'] ?? 'public') === 'public' ? 'selected' : '' }}>
                                        Local / Public (default)
                                    </option>
                                    <option value="s3" {{ ($settings['storage_disk'] ?? '') === 's3' ? 'selected' : '' }}>
                                        Amazon S3 / S3-Compatible
                                    </option>
                                </select>
                            </div>

                            {{-- S3 Fields (shown only when S3 selected) --}}
                            <div id="s3Fields" class="{{ ($settings['storage_disk'] ?? 'public') === 's3' ? '' : 'hidden' }} space-y-4 pt-2 border-t border-slate-100">
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">S3 Credentials</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">S3 Bucket Name</label>
                                        <input type="text" name="s3_bucket" value="{{ $settings['s3_bucket'] ?? '' }}"
                                               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                               placeholder="my-jaramarket-bucket">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Region</label>
                                        <input type="text" name="s3_region" value="{{ $settings['s3_region'] ?? 'us-east-1' }}"
                                               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                               placeholder="us-east-1">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Access Key ID</label>
                                        <input type="text" name="s3_access_key" value="{{ $settings['s3_access_key'] ?? '' }}"
                                               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 font-mono"
                                               placeholder="AKIAIOSFODNN7EXAMPLE">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Secret Access Key</label>
                                        <input type="password" name="s3_secret_key" value="{{ $settings['s3_secret_key'] ?? '' }}"
                                               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 font-mono"
                                               placeholder="••••••••••••••••••••">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">
                                            Custom URL <span class="text-slate-400 font-normal">(optional — CDN)</span>
                                        </label>
                                        <input type="text" name="s3_url" value="{{ $settings['s3_url'] ?? '' }}"
                                               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                               placeholder="https://cdn.example.com">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">
                                            Custom Endpoint <span class="text-slate-400 font-normal">(optional — DigitalOcean, Cloudflare R2)</span>
                                        </label>
                                        <input type="text" name="s3_endpoint" value="{{ $settings['s3_endpoint'] ?? '' }}"
                                               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                               placeholder="https://sgp1.digitaloceanspaces.com">
                                    </div>
                                </div>
                                <div class="flex items-start gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
                                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-xs text-amber-700">
                                        Changing storage driver only affects <strong>new uploads</strong>. Existing files on the old disk won't be migrated automatically.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

</form>
            </div>
        </div>
    </div>
</div>

<style>
.active-tab { background-color: #f0fdf4; color: #15803d; }
.active-tab .tab-icon { background-color: #dcfce7 !important; }
</style>
@endsection

@push('scripts')
<script>
function previewLogo(e) { if(e.target.files[0]) document.getElementById('logoPreview').src = URL.createObjectURL(e.target.files[0]); }
function previewFav(e)  { if(e.target.files[0]) document.getElementById('favPreview').src  = URL.createObjectURL(e.target.files[0]); }

$(function() {
    const savedTab = localStorage.getItem('settingsTab') || 'general';
    activateTab(savedTab);

    $('.settings-tab').on('click', function() {
        const tab = $(this).data('tab');
        localStorage.setItem('settingsTab', tab);
        activateTab(tab);
    });

    function activateTab(tab) {
        $('.tab-panel').addClass('hidden');
        $('#tab-' + tab).removeClass('hidden');
        $('.settings-tab').removeClass('active-tab').addClass('text-slate-600 hover:bg-slate-50');
        $('.settings-tab').each(function() {
            $(this).find('.tab-icon').removeClass('bg-emerald-100').addClass('bg-slate-100');
            $(this).find('svg').removeClass('text-emerald-600').addClass('text-slate-500');
        });
        const $btn = $('.settings-tab[data-tab="' + tab + '"]');
        $btn.addClass('active-tab').removeClass('text-slate-600 hover:bg-slate-50');
        $btn.find('.tab-icon').addClass('bg-emerald-100').removeClass('bg-slate-100');
        $btn.find('svg').addClass('text-emerald-600').removeClass('text-slate-500');
    }
});
</script>
@endpush

@push('scripts')
<script>
document.getElementById('storageDisk').addEventListener('change', function() {
    const s3 = document.getElementById('s3Fields');
    s3.classList.toggle('hidden', this.value !== 's3');
});
</script>
@endpush
