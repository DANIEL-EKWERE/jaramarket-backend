@extends('layouts.app')
@section('title', 'User Management')

@section('content')
<div class="min-h-screen bg-slate-50">

    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-slate-600 font-medium">User Management</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Customers</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('users.export') ?? '#' }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export
                </a>
            </div>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6 space-y-5">

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Users</p>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_users'] ?? 0) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Active</p>
                <p class="text-2xl font-bold text-emerald-600">{{ number_format($stats['active_users'] ?? 0) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">New This Month</p>
                <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['new_this_month'] ?? 0) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Suspended</p>
                <p class="text-2xl font-bold text-red-500">{{ number_format($stats['suspended_users'] ?? 0) }}</p>
            </div>
        </div>

        {{-- Users Table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">All Customers</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Manage customer accounts and access levels</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <svg class="absolute inset-y-0 left-3 my-auto w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="userSearch" placeholder="Search users…"
                               class="pl-9 pr-4 py-2 w-52 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-slate-50">
                    </div>
                    <select id="userStatusFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-600">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full text-sm" id="usersTable">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-8">#</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Phone</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Orders</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Spent</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Joined</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($users ?? [] as $i => $user)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-5 py-4 text-xs text-slate-400 font-mono">{{ $i + 1 }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 text-blue-700 text-xs font-semibold">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $user->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $user->phone ?? '—' }}</td>
                            <td class="px-5 py-4 text-slate-700 font-medium">{{ $user->orders_count ?? 0 }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-800">{{ $settings['currency'] ?? '₦' }}{{ number_format($user->total_spent ?? 0) }}</td>
                            <td class="px-5 py-4">
                                @if($user->is_active ?? true)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-600 border border-red-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Suspended
                                </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400">{{ $user->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('users.show', $user) ?? '#' }}"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                                        View
                                    </a>
                                    <form action="{{ route('users.toggle-status', $user) ?? '#' }}" method="POST" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                                                   {{ ($user->is_active ?? true) ? 'text-amber-600 bg-white border-amber-200 hover:bg-amber-50' : 'text-emerald-600 bg-white border-emerald-200 hover:bg-emerald-50' }}">
                                            {{ ($user->is_active ?? true) ? 'Suspend' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                                        <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-500">No users found</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($users) && method_exists($users, 'hasPages') && $users->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    function filterUsers() {
        const q = $('#userSearch').val().toLowerCase();
        const s = $('#userStatusFilter').val().toLowerCase();
        $('#usersTable tbody tr').each(function() {
            const matchQ = $(this).text().toLowerCase().includes(q);
            const matchS = !s || $(this).find('td:eq(5)').text().toLowerCase().includes(s);
            $(this).toggle(matchQ && matchS);
        });
    }
    $('#userSearch').on('keyup', filterUsers);
    $('#userStatusFilter').on('change', filterUsers);
});
</script>
@endpush
