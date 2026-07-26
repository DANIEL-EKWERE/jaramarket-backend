@extends('layouts.app')
@section('title', 'Application Settings')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- ── TOP BAR ─────────────────────────────────────────────────── --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>System</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">Settings</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Application Settings</h1>
            </div>
            <span class="hidden sm:block text-xs text-slate-400 bg-slate-100 px-3 py-1.5 rounded-lg font-medium">
                {{ now()->format('d M Y') }}
            </span>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6">

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="mb-5 flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="mb-5 flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ session('error') }}
        </div>
        @endif

        {{-- ── TAB NAV + CONTENT ──────────────────────────────────── --}}
        <div class="flex flex-col xl:flex-row gap-6">

            {{-- Sidebar Tab Nav --}}
            <div class="xl:w-56 flex-shrink-0">
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden sticky top-24">
                    <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/60">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Configuration</p>
                    </div>
                    <nav class="p-2 space-y-0.5" id="settings-nav">
                        <button onclick="switchTab('general')" data-tab="general"
                                class="settings-tab w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium text-left transition-colors">
                            <div class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 bg-blue-100">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            General
                        </button>
                        <button onclick="switchTab('delivery')" data-tab="delivery"
                                class="settings-tab w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium text-left transition-colors">
                            <div class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 bg-amber-100">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                            </div>
                            Delivery
                        </button>
                        <button onclick="switchTab('payment')" data-tab="payment"
                                class="settings-tab w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium text-left transition-colors">
                            <div class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 bg-emerald-100">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                            Payment
                        </button>
                        <button onclick="switchTab('notifications')" data-tab="notifications"
                                class="settings-tab w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium text-left transition-colors">
                            <div class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 bg-violet-100">
                                <svg class="w-3.5 h-3.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </div>
                            Notifications
                        </button>
                    </nav>
                </div>
            </div>

            {{-- Tab Panels --}}
            <div class="flex-1 min-w-0">

                {{-- ══ GENERAL ════════════════════════════════════════ --}}
                <div id="tab-general" class="settings-panel">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">General Settings</h2>
                                    <p class="text-xs text-slate-400">Basic application identity and locale</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-5">

                                <div>
                                    <label for="site_name" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Site Name</label>
                                    <input type="text" name="settings[site_name]" id="site_name"
                                           value="{{ $settings['site_name'] ?? '' }}"
                                           class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                           placeholder="e.g. My Food App">
                                </div>

                                <div>
                                    <label for="site_description" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Site Description</label>
                                    <textarea name="settings[site_description]" id="site_description" rows="3"
                                              class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors resize-none"
                                              placeholder="Brief description shown in meta tags and footers">{{ $settings['site_description'] ?? '' }}</textarea>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label for="contact_email" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Contact Email</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            </div>
                                            <input type="email" name="settings[contact_email]" id="contact_email"
                                                   value="{{ $settings['contact_email'] ?? '' }}"
                                                   class="w-full pl-10 pr-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                                   placeholder="hello@example.com">
                                        </div>
                                    </div>

                                    <div>
                                        <label for="currency" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Currency</label>
                                        <select name="settings[currency]" id="currency"
                                                class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors">
                                            <option value="NGN" {{ ($settings['currency'] ?? '') == 'NGN' ? 'selected' : '' }}>NGN (₦)</option>
                                            <option value="USD" {{ ($settings['currency'] ?? '') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                            <option value="EUR" {{ ($settings['currency'] ?? '') == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                            <option value="GBP" {{ ($settings['currency'] ?? '') == 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                                <button type="submit" name="section" value="general"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Save General Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- ══ DELIVERY ════════════════════════════════════════ --}}
                <div id="tab-delivery" class="settings-panel hidden">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Delivery Settings</h2>
                                    <p class="text-xs text-slate-400">Configure delivery options, fees and timeframes</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-5">

                                {{-- Enable Delivery toggle --}}
                                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">Enable Delivery</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Toggle delivery option for customers</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="enable_delivery" name="settings[enable_delivery]" value="1"
                                               {{ ($settings['enable_delivery'] ?? 0) == 1 ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label for="delivery_fee" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Default Delivery Fee (₦)</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                <span class="text-slate-400 text-sm font-semibold">₦</span>
                                            </div>
                                            <input type="text" name="settings[delivery_fee]" id="delivery_fee"
                                                   value="{{ $settings['delivery_fee'] ?? '0.00' }}"
                                                   class="w-full pl-8 pr-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                                   placeholder="0.00">
                                        </div>
                                    </div>

                                    <div>
                                        <label for="free_delivery_threshold" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Free Delivery Threshold (₦)</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                <span class="text-slate-400 text-sm font-semibold">₦</span>
                                            </div>
                                            <input type="text" name="settings[free_delivery_threshold]" id="free_delivery_threshold"
                                                   value="{{ $settings['free_delivery_threshold'] ?? '0.00' }}"
                                                   class="w-full pl-8 pr-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                                   placeholder="0.00">
                                        </div>
                                        <p class="mt-1.5 text-xs text-slate-400">Orders above this amount qualify for free delivery.</p>
                                    </div>

                                    <div>
                                        <label for="delivery_time" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Estimated Delivery Time (mins)</label>
                                        <div class="relative">
                                            <input type="number" name="settings[delivery_time]" id="delivery_time"
                                                   value="{{ $settings['delivery_time'] ?? '30' }}"
                                                   class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                                   placeholder="30">
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                                <button type="submit" name="section" value="delivery"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Save Delivery Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- ══ PAYMENT ═════════════════════════════════════════ --}}
                <div id="tab-payment" class="settings-panel hidden">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Payment Settings</h2>
                                    <p class="text-xs text-slate-400">Configure payment gateways and API keys</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-5">

                                {{-- Gateway toggles --}}
                                <div class="space-y-3">
                                    <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Active Gateways</p>

                                    @foreach([
                                        ['enable_paypal',  'PayPal',           'Accept payments via PayPal'],
                                        ['enable_stripe',  'Stripe',           'Accept card payments via Stripe'],
                                        ['enable_cod',     'Cash on Delivery', 'Allow cash payment on delivery'],
                                    ] as [$key, $label, $desc])
                                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">{{ $label }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ $desc }}</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="settings[{{ $key }}]" value="1"
                                                   {{ ($settings[$key] ?? 0) == 1 ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label for="paypal_client_id" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">PayPal Client ID</label>
                                        <input type="text" name="settings[paypal_client_id]" id="paypal_client_id"
                                               value="{{ $settings['paypal_client_id'] ?? '' }}"
                                               class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-800 font-mono placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                               placeholder="PayPal client ID">
                                    </div>
                                    <div>
                                        <label for="stripe_key" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Stripe Public Key</label>
                                        <input type="text" name="settings[stripe_key]" id="stripe_key"
                                               value="{{ $settings['stripe_key'] ?? '' }}"
                                               class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-800 font-mono placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                               placeholder="pk_live_…">
                                    </div>
                                </div>

                            </div>
                            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                                <button type="submit" name="section" value="payment"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Save Payment Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- ══ NOTIFICATIONS ═══════════════════════════════════ --}}
                <div id="tab-notifications" class="settings-panel hidden">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Notification Settings</h2>
                                    <p class="text-xs text-slate-400">Configure email triggers for customers and admins</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-5">

                                <div class="space-y-3">
                                    <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Email Triggers</p>

                                    @foreach([
                                        ['order_confirmation_email',      'Order Confirmation',       'Send email to customer when order is placed'],
                                        ['order_status_email',            'Order Status Updates',     'Send email when order status changes'],
                                        ['admin_new_order_notification',  'Admin New Order Alert',    'Send email to admin when a new order is placed'],
                                    ] as [$key, $label, $desc])
                                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">{{ $label }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ $desc }}</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="settings[{{ $key }}]" value="1"
                                                   {{ ($settings[$key] ?? 1) == 1 ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>

                                <div>
                                    <label for="admin_email" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Admin Notification Email</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </div>
                                        <input type="email" name="settings[admin_email]" id="admin_email"
                                               value="{{ $settings['admin_email'] ?? '' }}"
                                               class="w-full pl-10 pr-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                               placeholder="admin@example.com">
                                    </div>
                                    <p class="mt-1.5 text-xs text-slate-400">This address receives all admin-level order notifications.</p>
                                </div>

                            </div>
                            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                                <button type="submit" name="section" value="notification"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Save Notification Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>{{-- end panels --}}
        </div>{{-- end tab layout --}}
    </div>
</div>
@endsection

@push('scripts')
<script>
var ACTIVE_TAB_BTN = 'bg-slate-100 text-slate-900';
var INACTIVE_TAB_BTN = 'text-slate-500 hover:bg-slate-50 hover:text-slate-800';

function switchTab(tab) {
    // Hide all panels
    document.querySelectorAll('.settings-panel').forEach(function(p) {
        p.classList.add('hidden');
    });
    // Deactivate all nav buttons
    document.querySelectorAll('.settings-tab').forEach(function(b) {
        b.classList.remove('bg-slate-100', 'text-slate-900');
        b.classList.add('text-slate-500', 'hover:bg-slate-50', 'hover:text-slate-800');
    });
    // Show selected panel
    var panel = document.getElementById('tab-' + tab);
    if (panel) panel.classList.remove('hidden');
    // Activate selected button
    var btn = document.querySelector('[data-tab="' + tab + '"]');
    if (btn) {
        btn.classList.remove('text-slate-500', 'hover:bg-slate-50', 'hover:text-slate-800');
        btn.classList.add('bg-slate-100', 'text-slate-900');
    }
    // Persist tab choice
    try { localStorage.setItem('settingsTab', tab); } catch(e) {}
}

$(function () {
    // Restore last tab or default to general
    var saved = 'general';
    try { saved = localStorage.getItem('settingsTab') || 'general'; } catch(e) {}
    switchTab(saved);
});
</script>
@endpush
