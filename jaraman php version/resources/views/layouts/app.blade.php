<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ company('site_name', config('app.name', 'JaraMarket')) }}</title>
    <link rel="icon" href="{{ get_media_url(company('favicon_logo')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <style>
        [x-cloak]{display:none!important}
        body{font-family:'Inter',sans-serif}
        .badge-success{display:inline-flex;align-items:center;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:#d1fae5;color:#065f46}
        .badge-danger{display:inline-flex;align-items:center;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:#fee2e2;color:#991b1b}
        .badge-warning{display:inline-flex;align-items:center;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:#fef3c7;color:#92400e}
        .badge-info{display:inline-flex;align-items:center;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:#dbeafe;color:#1e40af}
        .btn-xs-primary{display:inline-flex;align-items:center;padding:4px 10px;font-size:11px;font-weight:500;color:#fff;background:#16a34a;border-radius:6px}
        .btn-xs-primary:hover{background:#15803d}
        .btn-xs-secondary{display:inline-flex;align-items:center;padding:4px 10px;font-size:11px;font-weight:500;color:#374151;background:#fff;border:1px solid #d1d5db;border-radius:6px}
        .btn-xs-secondary:hover{background:#f9fafb}
        .btn-xs-danger{display:inline-flex;align-items:center;padding:4px 10px;font-size:11px;font-weight:500;color:#fff;background:#dc2626;border-radius:6px}
        .nav-link{display:inline-flex;align-items:center;gap:10px;width:100%;padding:9px 14px;font-size:13.5px;color:#d1fae5;border-radius:8px;transition:background .15s}
        .nav-link:hover,.nav-link.active{background:rgba(0,0,0,.25);color:#fff}
        .nav-section{padding:14px 14px 4px;font-size:10px;font-weight:700;color:#6ee7b7;text-transform:uppercase;letter-spacing:.08em}
        input[type=text],input[type=email],input[type=password],input[type=number],input[type=date],select,textarea{width:100%;padding:9px 12px;font-size:14px;border:1px solid #e2e8f0;border-radius:8px;outline:none;transition:border .15s,box-shadow .15s}
        input:focus,select:focus,textarea:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.15)}
        label{display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:5px}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px}
        .page-title{font-size:22px;font-weight:700;color:#0f172a}
        .page-subtitle{font-size:13px;color:#64748b;margin-top:2px}
        table.dataTable tbody tr{border-bottom:1px solid #f1f5f9}
        table.dataTable thead th{background:#f8fafc;font-weight:600;font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #e2e8f0!important}
        .dataTables_wrapper .dataTables_paginate .paginate_button{padding:4px 10px!important;border-radius:6px!important;font-size:13px!important}
        .dataTables_wrapper .dataTables_paginate .paginate_button.current{background:#16a34a!important;color:#fff!important;border:none!important}
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 antialiased">

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 bg-green-700 shadow-xl transform -translate-x-full md:translate-x-0 transition-transform duration-300">
        <!-- Logo -->
        <div class="flex items-center gap-3 px-5 py-4 border-b border-green-600">
            <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/></svg>
            </div>
            <a href="{{ route('dashboard') }}" class="text-lg font-bold text-white">{{ company('site_name','JaraMarket') }}</a>
        </div>
        <!-- User info -->
        <div class="flex items-center gap-3 px-5 py-3 bg-green-800/40 border-b border-green-600">
            <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white font-bold text-sm">{{ strtoupper(substr(auth()->user()->firstname??'A',0,1)) }}</div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-green-300">{{ \App\Enums\UserPermissionsEnum::from(auth()->user()->role)->label() }}</p>
            </div>
        </div>
        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto px-3 py-3">
            @if(auth()->user()->hasPermission('view_dashboard'))
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            @endif

            @if(auth()->user()->hasPermission('view_orders'))
            <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Orders
            </a>
            @endif

            @if(auth()->user()->hasAnyPermission(['view_products','view_ingredients','view_categories']))
            <p class="nav-section">Catalogue</p>
            @if(auth()->user()->hasPermission('view_products'))
            <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                Food / Products
            </a>
            @endif
            @if(auth()->user()->hasPermission('view_ingredients'))
            <a href="{{ route('ingredients.index') }}" class="nav-link {{ request()->routeIs('ingredients*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Ingredients
            </a>
            @endif
            @if(auth()->user()->hasPermission('view_categories'))
            <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                Categories
            </a>
            @endif
            @endif

            @if(auth()->user()->hasAnyPermission(['view_users','view_vendors']))
            <p class="nav-section">People</p>
            @if(auth()->user()->hasPermission('view_users'))
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Customers
            </a>
            @endif
            @if(auth()->user()->hasPermission('view_vendors'))
            <a href="{{ route('admin.vendors.index') }}" class="nav-link {{ request()->routeIs('admin.vendors*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Vendors
            </a>
            @endif
            @endif

            @if(auth()->user()->hasAnyPermission(['view_transactions','view_wallets','manage_withdrawals','view_commissions']))
            <p class="nav-section">Finance</p>
            @if(auth()->user()->hasPermission('view_transactions'))
            <a href="{{ route('admin.finance.transactions') }}" class="nav-link {{ request()->routeIs('admin.finance.transactions*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Transactions
            </a>
            @endif
            @if(auth()->user()->hasPermission('view_wallets'))
            <a href="{{ route('admin.finance.wallets') }}" class="nav-link {{ request()->routeIs('admin.finance.wallets*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Wallets
            </a>
            @endif
            @if(auth()->user()->hasPermission('manage_withdrawals'))
            <a href="{{ route('admin.finance.withdrawals') }}" class="nav-link {{ request()->routeIs('admin.finance.withdrawals*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                Withdrawals
            </a>
            @endif
            @if(auth()->user()->hasPermission('view_commissions'))
            <a href="{{ route('commissions.index') }}" class="nav-link {{ request()->routeIs('commissions*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                Commissions
            </a>
            @endif
            @endif

            @if(auth()->user()->hasPermission('view_reports'))
            <p class="nav-section">Reports</p>
            <a href="{{ route('reports.orders') }}" class="nav-link {{ request()->routeIs('reports*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Reports
            </a>
            <a href="{{ route('summary') }}" class="nav-link {{ request()->routeIs('summary*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Summary
            </a>
            @endif

            @if(auth()->user()->hasAnyPermission(['manage_admins','manage_settings']))
            <p class="nav-section">System</p>
            @if(auth()->user()->hasPermission('manage_admins'))
            <a href="{{ route('admin-management.index') }}" class="nav-link {{ request()->routeIs('admin-management*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Admin Users
            </a>
            @endif
            @if(auth()->user()->hasPermission('manage_settings'))
            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings*') ? 'active':'' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
            @endif
            @endif
        </nav>

        <!-- Logout -->
        <div class="border-t border-green-600 px-3 py-3">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="nav-link w-full text-left text-red-300 hover:text-white hover:bg-red-700/40">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="flex flex-col flex-1 md:ml-64 overflow-hidden">
        <!-- Topbar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
            <div class="flex items-center justify-between px-4 sm:px-6 py-3">
                <button id="sidebarToggle" class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex-1"></div>
                <div class="flex items-center gap-3">
                    {{-- Notification bell dropdown --}}
                    <div x-data="notificationBell()" class="relative">
                        <button @click="toggle()"
                                class="relative p-2 text-slate-500 hover:bg-slate-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span x-show="unreadCount > 0"
                                  class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 bg-red-500 text-white rounded-full flex items-center justify-center"
                                  style="font-size:10px;font-weight:700;line-height:1;">
                                <span x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
                            </span>
                        </button>

                        {{-- Dropdown panel --}}
                        <div x-show="open"
                             @click.outside="open = false"
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 overflow-hidden"
                             style="top: 100%;">

                            {{-- Header --}}
                            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-semibold text-slate-900">Notifications</h3>
                                    <span x-show="unreadCount > 0"
                                          class="px-1.5 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-bold"
                                          x-text="unreadCount"></span>
                                </div>
                                <button @click="markAllRead()"
                                        x-show="unreadCount > 0"
                                        class="text-xs text-emerald-600 hover:text-emerald-700 font-medium hover:underline">
                                    Mark all read
                                </button>
                            </div>

                            {{-- Body --}}
                            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">

                                {{-- Loading state --}}
                                <div x-show="loading" class="flex items-center justify-center py-8 gap-2">
                                    <svg class="w-4 h-4 text-slate-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    <span class="text-xs text-slate-400">Loading…</span>
                                </div>

                                {{-- Empty state --}}
                                <div x-show="!loading && notifications.length === 0" class="py-10 text-center">
                                    <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                    <p class="text-sm text-slate-400 font-medium">All caught up!</p>
                                    <p class="text-xs text-slate-300 mt-0.5">No notifications yet</p>
                                </div>

                                {{-- Notification items --}}
                                <template x-for="n in notifications" :key="n.id">
                                    <div @click="markRead(n.id)"
                                         :class="n.is_read ? 'bg-white' : 'bg-emerald-50/60'"
                                         class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 cursor-pointer transition-colors">
                                        {{-- Icon dot --}}
                                        <div :class="n.is_read ? 'bg-slate-200' : 'bg-emerald-500'"
                                             class="w-2 h-2 rounded-full flex-shrink-0 mt-1.5"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-slate-800 leading-snug"
                                               :class="n.is_read ? 'font-normal' : 'font-medium'"
                                               x-text="n.message || n.title"></p>
                                            <p class="text-xs text-slate-400 mt-1" x-text="n.created_at"></p>
                                        </div>
                                        <span x-show="!n.is_read"
                                              class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0 mt-2"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Footer --}}
                            <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50">
                                <a href="#" class="block text-center text-xs text-emerald-600 hover:text-emerald-700 font-medium hover:underline">
                                    View all notifications
                                </a>
                            </div>
                        </div>
                    </div>
                    <div x-data="{open:false}" class="relative">
                        <button @click="open=!open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-100">
                            <div class="w-7 h-7 rounded-full bg-green-600 flex items-center justify-center text-white text-xs font-bold">{{ strtoupper(substr(auth()->user()->firstname??'A',0,1)) }}</div>
                            <span class="hidden sm:block text-sm font-medium text-slate-700">{{ auth()->user()->firstname }}</span>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.outside="open=false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50">
                            <a href="{{ route('admin.profile') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">My Profile</a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 overflow-y-auto">
            <div class="px-4 sm:px-6 lg:px-8 py-6">
                @if(session('success'))
                <div class="mb-5 flex items-start gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
                @endif
                @if(session('error'))
                <div class="mb-5 flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    {{ session('error') }}
                </div>
                @endif
                @if($errors->any())
                <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-800">
                    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script>
$(function(){
    $('#sidebarToggle').on('click',function(){$('#sidebar').toggleClass('-translate-x-full translate-x-0');});
    $(document).on('click',function(e){
        if($(window).width()<768&&!$(e.target).closest('#sidebar,#sidebarToggle').length){
            $('#sidebar').addClass('-translate-x-full').removeClass('translate-x-0');
        }
    });
});
</script>

<script>
// Alpine.js notification bell component — defined before Alpine initialises
// Using document.addEventListener to ensure Alpine is ready
document.addEventListener('alpine:init', () => {
    Alpine.data('notificationBell', () => ({
        open: false,
        notifications: [],
        unreadCount: {{ auth()->user()->unreadNotifications()->count() }},
        loading: false,
        panelUrl: '{{ route("notifications.panel") }}',
        markAllUrl: '{{ route("notifications.markAllAsRead") }}',
        csrfToken: document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').content : '',

        toggle() {
            this.open = !this.open;
            if (this.open && this.notifications.length === 0) {
                this.load();
            }
        },

        load() {
            this.loading = true;
            fetch(this.panelUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                }
            })
            .then(r => r.json())
            .then(d => { this.notifications = d.data || []; this.loading = false; })
            .catch(() => { this.loading = false; });
        },

        markAllRead() {
            fetch(this.markAllUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken,
                }
            }).then(() => {
                this.unreadCount = 0;
                this.notifications = this.notifications.map(n => ({ ...n, is_read: true }));
            });
        },

        markRead(id) {
            fetch('/notifications/' + id + '/read', {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(() => {
                const n = this.notifications.find(n => n.id === id);
                if (n && !n.is_read) {
                    n.is_read = true;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
            });
        }
    }));
});
</script>
@stack('scripts')
</body>
</html>
