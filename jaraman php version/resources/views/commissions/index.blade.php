@extends('layouts.app')
@section('title', 'Commissions')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span>Accounting</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">Commissions</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Commission Tiers</h1>
            </div>
            <a href="{{ route('commissions.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Tier
            </a>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6 space-y-5">

        @if(session('success'))
        <div class="flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- SUMMARY CARDS --}}
        <div class="grid grid-cols-2 xl:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Tiers</p><p class="text-3xl font-bold text-slate-900 mt-1.5">{{ $commissions->total() }}</p><p class="text-xs text-slate-400 mt-2">Configured ranges</p></div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Lowest Rate</p><p class="text-3xl font-bold text-emerald-600 mt-1.5">{{ $commissions->min('percentage') ?? '—' }}%</p><p class="text-xs text-slate-400 mt-2">Minimum commission</p></div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5 col-span-2 xl:col-span-1">
                <div class="flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Highest Rate</p><p class="text-3xl font-bold text-orange-500 mt-1.5">{{ $commissions->max('percentage') ?? '—' }}%</p><p class="text-xs text-slate-400 mt-2">Maximum commission</p></div>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0V15m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- TIERS TABLE --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Commission Rate Table</h2>
                    <p class="text-xs text-slate-400">Tiered rates applied per order value range</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-10">#</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Min Amount (₦)</th>
                            <th class="px-4 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Max Amount (₦)</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Rate (%)</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Range Visual</th>
                            <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Created</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($commissions as $i => $commission)
                        @php
                            $maxAll = $commissions->max('max_amount') ?: 1;
                            $pct = round(($commission->max_amount / $maxAll) * 100);
                            $colors = ['bg-indigo-500','bg-emerald-500','bg-blue-500','bg-violet-500','bg-orange-500'];
                            $barColor = $colors[$i % count($colors)];
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-4 text-slate-400 text-xs font-mono">{{ $commission->id }}</td>
                            <td class="px-4 py-4 text-right font-mono font-semibold text-slate-700 text-xs">₦{{ number_format($commission->min_amount, 2) }}</td>
                            <td class="px-4 py-4 text-right font-mono font-semibold text-slate-700 text-xs">₦{{ number_format($commission->max_amount, 2) }}</td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-block px-3 py-1 rounded-lg text-sm font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    {{ $commission->percentage }}%
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden max-w-32">
                                        <div class="h-full {{ $barColor }} rounded-full" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-400 font-mono">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-400 text-xs">{{ $commission->created_at?->format('d M Y') ?? '—' }}</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('commissions.edit', $commission->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </a>
                                    <form action="{{ route('commissions.destroy', $commission->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this commission tier?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-500 bg-red-50 hover:bg-red-100 border border-red-100 rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <p class="text-slate-400 text-sm">No commission tiers configured</p>
                                <a href="{{ route('commissions.create') }}" class="mt-1 text-xs text-emerald-600 hover:text-emerald-800 font-medium">Add your first tier →</a>
                            </div>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($commissions->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">{{ $commissions->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
