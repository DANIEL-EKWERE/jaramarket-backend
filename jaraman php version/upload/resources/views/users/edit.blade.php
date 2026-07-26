@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- ── TOP BAR ─────────────────────────────────────────────────── --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <a href="{{ route('users.index') }}" class="hover:text-slate-600 transition-colors">Users</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">Edit User</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Edit User Profile</h1>
            </div>
            <a href="{{ route('users.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Users
            </a>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6">

        {{-- ── VALIDATION ERRORS ───────────────────────────────────── --}}
        @if($errors->any())
        <div class="mb-5 flex items-start gap-3 px-4 py-3.5 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="font-semibold mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-0.5 text-red-700">
                    @foreach($errors->all() as $error)
                        <li class="text-xs">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if(session('success'))
        <div class="mb-5 flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- ── MAIN LAYOUT ─────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- LEFT — Main form (8 cols) --}}
            <div class="xl:col-span-8 space-y-5">

                <form action="{{ route('users.update', $user) }}" method="POST" id="user-form">
                    @csrf
                    @method('PUT')

                    {{-- ── PERSONAL INFORMATION ──────────────────── --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Personal Information</h2>
                                <p class="text-xs text-slate-400">Full name and contact details</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                                {{-- First Name --}}
                                <div>
                                    <label for="firstname" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="firstname" id="firstname"
                                           value="{{ old('firstname', $user->firstname) }}"
                                           class="w-full px-4 py-2.5 border {{ $errors->has('firstname') ? 'border-red-400 bg-red-50' : 'border-slate-300 bg-slate-50' }} rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                           placeholder="Enter first name">
                                    @error('firstname')
                                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                {{-- Last Name --}}
                                <div>
                                    <label for="lastname" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="lastname" id="lastname"
                                           value="{{ old('lastname', $user->lastname) }}"
                                           class="w-full px-4 py-2.5 border {{ $errors->has('lastname') ? 'border-red-400 bg-red-50' : 'border-slate-300 bg-slate-50' }} rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                           placeholder="Enter last name">
                                    @error('lastname')
                                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                {{-- Email --}}
                                <div class="sm:col-span-2">
                                    <label for="email" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                            </svg>
                                        </div>
                                        <input type="email" name="email" id="email"
                                               value="{{ old('email', $user->email) }}"
                                               class="w-full pl-10 pr-4 py-2.5 border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-slate-300 bg-slate-50' }} rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                               placeholder="user@example.com">
                                    </div>
                                    @error('email')
                                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- ── SECURITY ───────────────────────────────── --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Security</h2>
                                <p class="text-xs text-slate-400">Leave password fields blank to keep the current password</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                                {{-- New Password --}}
                                <div>
                                    <label for="password" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                        New Password
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                        </div>
                                        <input type="password" name="password" id="password"
                                               class="w-full pl-10 pr-10 py-2.5 border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-slate-300 bg-slate-50' }} rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                               placeholder="Leave blank to keep current">
                                        <button type="button" onclick="togglePassword('password', this)"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    @error('password')
                                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                {{-- Confirm Password --}}
                                <div>
                                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                                        Confirm Password
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                        </div>
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                               class="w-full pl-10 pr-10 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors"
                                               placeholder="Re-enter new password">
                                        <button type="button" onclick="togglePassword('password_confirmation', this)"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </form>{{-- end form --}}

                {{-- ── ORDER HISTORY ──────────────────────────────── --}}
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Order History</h2>
                                <p class="text-xs text-slate-400">All orders placed by this user</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-100 px-2.5 py-1 rounded-full">
                            {{ $user->orders->count() }} {{ Str::plural('order', $user->orders->count()) }}
                        </span>
                    </div>

                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount (₦)</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @forelse($user->orders as $order)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-5 py-4">
                                        <span class="font-mono text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-1 rounded-md">
                                            {{ $order->reference ?? '#' . $order->id }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-slate-500 whitespace-nowrap">
                                        {{ $order->created_at->format('d M Y') }}
                                        <span class="block text-slate-400">{{ $order->created_at->format('H:i') }}</span>
                                    </td>
                                    <td class="px-4 py-4 text-right font-semibold text-slate-800 tabular-nums text-sm">
                                        {{ number_format($order->total, 2) }}
                                    </td>
                                    <td class="px-4 py-4">
                                        @php
                                            $map = [
                                                'completed'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
                                                'processing' => ['bg-blue-50 text-blue-700 border-blue-200',          'bg-blue-500'],
                                                'pending'    => ['bg-amber-50 text-amber-700 border-amber-200',        'bg-amber-400'],
                                                'cancelled'  => ['bg-red-50 text-red-600 border-red-200',              'bg-red-500'],
                                            ];
                                            [$cls, $dot] = $map[$order->status] ?? ['bg-slate-100 text-slate-500 border-slate-200', 'bg-slate-400'];
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $cls }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('orders.show', $order) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 hover:border-slate-300 transition-colors">
                                            View
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-500">No orders yet</p>
                                            <p class="text-xs text-slate-400">This user has not placed any orders.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($user->orders->count() > 0)
                    <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <p class="text-xs text-slate-400">{{ $user->orders->count() }} total {{ Str::plural('order', $user->orders->count()) }}</p>
                        <p class="text-xs font-semibold text-slate-600">
                            Total spent:
                            <span class="text-slate-900 tabular-nums">₦{{ number_format($user->orders->sum('total'), 2) }}</span>
                        </p>
                    </div>
                    @endif
                </div>

            </div>{{-- end left col --}}

            {{-- RIGHT SIDEBAR (4 cols) --}}
            <div class="xl:col-span-4 space-y-5">

                {{-- ── USER PROFILE CARD ──────────────────────────── --}}
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-5 flex flex-col items-center text-center border-b border-slate-100">
                        <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 text-2xl font-bold mb-3">
                            {{ strtoupper(substr($user->firstname ?? $user->name ?? 'U', 0, 1)) }}
                        </div>
                        <p class="text-base font-bold text-slate-900">
                            {{ trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: ($user->name ?? 'Unknown User') }}
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $user->email }}</p>
                        <div class="mt-3">
                            @if($user->is_active)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Inactive
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="px-5 py-4 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Member Since</span>
                            <span class="text-slate-800 font-semibold">{{ $user->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Total Orders</span>
                            <span class="text-slate-800 font-semibold tabular-nums">{{ $user->orders->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Total Spent</span>
                            <span class="text-slate-800 font-semibold tabular-nums">₦{{ number_format($user->orders->sum('total'), 2) }}</span>
                        </div>
                        @if($user->updated_at != $user->created_at)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Last Updated</span>
                            <span class="text-slate-800 font-semibold">{{ $user->updated_at->format('d M Y') }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ── ACCOUNT STATUS ─────────────────────────────── --}}
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-800">Account Status</h2>
                            <p class="text-xs text-slate-400">Control user access</p>
                        </div>
                    </div>
                    <div class="p-5">
                        {{-- Status tied to the main form above --}}
                        <label for="is_active" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                            Status
                        </label>
                        <select id="is_active" name="is_active" form="user-form"
                                class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-colors">
                            <option value="1" {{ old('is_active', $user->is_active) ? 'selected' : '' }}>
                                ✓ Active — Can log in and place orders
                            </option>
                            <option value="0" {{ old('is_active', $user->is_active) ? '' : 'selected' }}>
                                ✕ Inactive — Access suspended
                            </option>
                        </select>
                        <p class="mt-2 text-xs text-slate-400">Inactive users cannot log in or place new orders.</p>
                    </div>
                </div>

                {{-- ── FORM ACTIONS ───────────────────────────────── --}}
                <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                    <button type="submit" form="user-form"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                    <a href="{{ route('users.index') }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Cancel
                    </a>
                </div>

            </div>{{-- end sidebar --}}

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePassword(fieldId, btn) {
    var input = document.getElementById(fieldId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.classList.add('text-emerald-500');
        btn.classList.remove('text-slate-400');
    } else {
        input.type = 'password';
        btn.classList.remove('text-emerald-500');
        btn.classList.add('text-slate-400');
    }
}
</script>
@endpush
