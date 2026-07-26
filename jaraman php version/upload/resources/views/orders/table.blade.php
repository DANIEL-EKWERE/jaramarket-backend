{{--
    table.blade.php — server-rendered fallback / email/print partial.
    Column order must match the DataTable columns definition in index.blade.php:
    # | Reference | Customer | Amount | Status | Meal Prep | Date | Actions
--}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="w-full overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-10">#</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Reference</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                    <th class="px-4 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount (₦)</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Meal Prep</th>
                    <th class="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                    <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($orders as $i => $order)
                <tr class="hover:bg-slate-50/60 transition-colors">

                    {{-- # --}}
                    <td class="px-5 py-4 text-xs text-slate-400 font-mono">{{ $i + 1 }}</td>

                    {{-- Reference --}}
                    <td class="px-4 py-4">
                        <span class="font-mono font-semibold text-slate-800 text-xs bg-slate-100 px-1.5 py-0.5 rounded">
                            {{ $order->reference }}
                        </span>
                    </td>

                    {{-- Customer --}}
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 text-emerald-700 text-xs font-bold">
                                {{ strtoupper(substr($order->user->name, 0, 1)) }}
                            </div>
                            <span class="font-medium text-slate-700">{{ $order->user->name }}</span>
                        </div>
                    </td>

                    {{-- Amount --}}
                    <td class="px-4 py-4 text-right font-mono font-semibold text-slate-800">
                        ₦{{ number_format($order->total, 2) }}
                    </td>

                    {{-- Status --}}
                    <td class="px-4 py-4">
                        @php
                            $statusMap = [
                                'completed'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
                                'processing' => ['bg-blue-50 text-blue-700 border-blue-200',          'bg-blue-500'],
                                'pending'    => ['bg-amber-50 text-amber-700 border-amber-200',        'bg-amber-400'],
                                'cancelled'  => ['bg-red-50 text-red-600 border-red-200',              'bg-red-500'],
                            ];
                            [$cls, $dot] = $statusMap[$order->status] ?? ['bg-slate-100 text-slate-500 border-slate-200', 'bg-slate-400'];
                        @endphp
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $cls }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>

                    {{-- Meal Prep --}}
                    <td class="px-4 py-4 max-w-xs">
                        <span class="text-slate-500 text-xs block truncate" title="{{ $order->meal_prep ?? 'No instructions' }}">
                            {{ $order->meal_prep ?? 'No instructions' }}
                        </span>
                    </td>

                    {{-- Date --}}
                    <td class="px-4 py-4 text-slate-500 text-xs whitespace-nowrap">
                        {{ $order->created_at->format('d M Y') }}
                        <span class="block text-slate-400">{{ $order->created_at->format('H:i') }}</span>
                    </td>

                    {{-- Actions --}}
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
                    <td colspan="8" class="px-6 py-14 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-slate-500">No orders found</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
