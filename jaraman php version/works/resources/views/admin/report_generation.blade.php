@extends('layouts.app')
@section('title', 'Report Generation')

@section('content')
<div class="min-h-screen bg-slate-50">

    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-slate-600 font-medium">Report Generation</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Business Reports</h1>
            </div>
            <p class="text-xs text-slate-400 hidden sm:block">{{ now()->format('l, d M Y') }}</p>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6 space-y-6">

        {{-- KPI Summary --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Revenue (MTD)</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-900">{{ $settings['currency'] ?? '₦' }}{{ number_format($report['revenue_mtd'] ?? 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">Month to date</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Orders (MTD)</span>
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($report['orders_mtd'] ?? 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">Month to date</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Commissions</span>
                    <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-900">{{ $settings['currency'] ?? '₦' }}{{ number_format($report['commissions_mtd'] ?? 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">Paid to representatives</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Avg. Order Value</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-900">{{ $settings['currency'] ?? '₦' }}{{ number_format($report['avg_order_value'] ?? 0) }}</p>
                <p class="text-xs text-slate-400 mt-1">Per order this month</p>
            </div>
        </div>

        {{-- Report Generator Form --}}
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <div class="xl:col-span-4">
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden sticky top-24">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-800">Generate Report</h2>
                            <p class="text-xs text-slate-400">Configure filters & export</p>
                        </div>
                    </div>

                    <form action="{{ route('reports.generate') ?? '#' }}" method="POST" class="p-6 space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Report Type <span class="text-red-500">*</span></label>
                            <select name="report_type" required
                                    class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">— Select type —</option>
                                <option value="sales">Sales Report</option>
                                <option value="orders">Orders Report</option>
                                <option value="commissions">Commission Report</option>
                                <option value="representatives">Representatives Performance</option>
                                <option value="products">Product Sales</option>
                                <option value="customers">Customer Activity</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Date From <span class="text-red-500">*</span></label>
                            <input type="date" name="date_from" required
                                   value="{{ old('date_from', now()->startOfMonth()->format('Y-m-d')) }}"
                                   class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Date To <span class="text-red-500">*</span></label>
                            <input type="date" name="date_to" required
                                   value="{{ old('date_to', now()->format('Y-m-d')) }}"
                                   class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">State</label>
                            <select name="state_id"
                                    class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">All States</option>
                                @foreach($states ?? [] as $state)
                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Export Format</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach(['pdf' => 'PDF', 'excel' => 'Excel', 'csv' => 'CSV'] as $val => $label)
                                <label class="flex items-center justify-center gap-1.5 p-2 border rounded-lg cursor-pointer text-xs font-medium transition-colors has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-700 border-slate-200 text-slate-600 hover:border-slate-300">
                                    <input type="radio" name="format" value="{{ $val }}" {{ $val === 'pdf' ? 'checked' : '' }} class="sr-only">
                                    {{ $label }}
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow mt-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Generate & Download
                        </button>
                    </form>
                </div>
            </div>

            {{-- Report Preview Table --}}
            <div class="xl:col-span-8 space-y-5">
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Sales Overview</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Revenue breakdown by representative — {{ now()->format('M Y') }}</p>
                        </div>
                    </div>

                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Representative</th>
                                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">State</th>
                                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Orders</th>
                                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Revenue</th>
                                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Commission</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @forelse($reportData ?? [] as $row)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $row->rep_name }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $row->state }}</td>
                                    <td class="px-5 py-4 text-slate-700">{{ number_format($row->total_orders) }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $settings['currency'] ?? '₦' }}{{ number_format($row->total_revenue, 2) }}</td>
                                    <td class="px-5 py-4 text-violet-700 font-semibold">{{ $settings['currency'] ?? '₦' }}{{ number_format($row->commission_earned, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <p class="text-sm text-slate-400">Select a report type and generate to see data here.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if(count($reportData ?? []) > 0)
                            <tfoot>
                                <tr class="bg-slate-50 border-t border-slate-200">
                                    <td class="px-5 py-3 text-xs font-bold text-slate-700 uppercase tracking-wider" colspan="2">Totals</td>
                                    <td class="px-5 py-3 font-bold text-slate-800">{{ number_format(collect($reportData)->sum('total_orders')) }}</td>
                                    <td class="px-5 py-3 font-bold text-slate-800">{{ $settings['currency'] ?? '₦' }}{{ number_format(collect($reportData)->sum('total_revenue'), 2) }}</td>
                                    <td class="px-5 py-3 font-bold text-violet-700">{{ $settings['currency'] ?? '₦' }}{{ number_format(collect($reportData)->sum('commission_earned'), 2) }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
