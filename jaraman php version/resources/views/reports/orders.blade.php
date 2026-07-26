@extends('layouts.app')

@section('title', 'Order Reports')

@section('content')
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="md:flex md:items-center md:justify-between mb-6">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Order Reports
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Analyze order data and generate reports for your business.
                    </p>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <button type="button" id="export-csv"
                        class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export CSV
                    </button>
                    <button type="button" id="export-pdf"
                        class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export PDF
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <form action="{{ route('reports.orders') }}" method="GET" class="space-y-6">
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                                <div class="mt-1">
                                    <input type="date" name="date_from" id="date_from"
                                        value="{{ request('date_from', now()->subDays(30)->format('Y-m-d')) }}"
                                        class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                                <div class="mt-1">
                                    <input type="date" name="date_to" id="date_to"
                                        value="{{ request('date_to', now()->format('Y-m-d')) }}"
                                        class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="status" class="block text-sm font-medium text-gray-700">Order Status</label>
                                <div class="mt-1">
                                    <select id="status" name="status"
                                        class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        <option value="">All Statuses</option>
                                        @php
                                            $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                                        @endphp
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status }}"
                                                {{ request('status') == $status ? 'selected' : '' }}>
                                                {{ ucfirst($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment
                                    Method</label>
                                <div class="mt-1">
                                    <select id="payment_method" name="payment_method"
                                        class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        <option value="">All Methods</option>
                                        @php
                                            $methods = ['credit_card', 'paypal', 'bank_transfer', 'cash_on_delivery'];
                                        @endphp
                                        @foreach ($methods as $method)
                                            <option value="{{ $method }}"
                                                {{ request('payment_method') == $method ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('_', ' ', $method)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="customer" class="block text-sm font-medium text-gray-700">Customer</label>
                                <div class="mt-1">
                                    <select id="customer" name="customer"
                                        class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        <option value="">All Customers</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                {{ request('customer') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="sm:col-span-6 flex justify-end">
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Generate Report
                                </button>
                                @if (request()->anyFilled(['date_from', 'date_to', 'status', 'payment_method', 'customer']))
                                    <a href="{{ route('reports.orders') }}"
                                        class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        Clear Filters
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <!-- Total Orders Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Total Orders
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            {{ $totalOrders }}
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:px-6">
                        <div class="text-sm">
                            <span class="font-medium text-gray-500">
                                {{ $orderGrowth >= 0 ? '+' : '' }}{{ $orderGrowth }}% from previous period
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Total Revenue
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            ₦{{ number_format($totalRevenue, 2) }}
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:px-6">
                        <div class="text-sm">
                            <span class="font-medium text-gray-500">
                                {{ $revenueGrowth >= 0 ? '+' : '' }}{{ $revenueGrowth }}% from previous period
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Average Order Value Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Average Order Value
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            ₦{{ number_format($averageOrderValue, 2) }}
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:px-6">
                        <div class="text-sm">
                            <span class="font-medium text-gray-500">
                                {{ $aovGrowth >= 0 ? '+' : '' }}{{ $aovGrowth }}% from previous period
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Conversion Rate Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Conversion Rate
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            {{ number_format($conversionRate, 2) }}%
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:px-6">
                        <div class="text-sm">
                            <span class="font-medium text-gray-500">
                                {{ $conversionGrowth >= 0 ? '+' : '' }}{{ $conversionGrowth }}% from previous period
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 mb-6">
                <!-- Orders Over Time Chart -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Orders Over Time</h3>
                        <div class="mt-2 h-64">
                            <canvas id="ordersChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Revenue by Status Chart -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Revenue by Status</h3>
                        <div class="mt-2 h-64">
                            <canvas id="revenueByStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Order ID
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Customer
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Payment Method
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Items
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($orders as $order)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                #{{ $order->id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $order->user->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $order->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        'processing' => 'bg-green-100 text-green-800',
                                                        'shipped' => 'bg-purple-100 text-purple-800',
                                                        'delivered' => 'bg-green-100 text-green-800',
                                                        'cancelled' => 'bg-red-100 text-red-800',
                                                    ];
                                                    $statusColor =
                                                        $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800';
                                                @endphp
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $order->items_count }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                ₦{{ number_format($order->total, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('orders.show', $order) }}"
                                                    class="text-green-600 hover:text-green-900">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8"
                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                No orders found for the selected criteria.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $orders->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Orders Over Time Chart
            const ordersCtx = document.getElementById('ordersChart').getContext('2d');
            const ordersChart = new Chart(ordersCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($orderChartLabels) !!},
                    datasets: [{
                            label: 'Orders',
                            data: {!! json_encode($orderChartData) !!},
                            backgroundColor: 'rgba(16, 185, 129, 0.2)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 2,
                            tension: 0.3,
                            pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                        },
                        {
                            label: 'Revenue',
                            data: {!! json_encode($revenueChartData) !!},
                            backgroundColor: 'rgba(79, 70, 229, 0.2)',
                            borderColor: 'rgba(79, 70, 229, 1)',
                            borderWidth: 2,
                            tension: 0.3,
                            pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Orders'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    }
                }
            });

            // Revenue by Status Chart
            const statusCtx = document.getElementById('revenueByStatusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($statusChartLabels) !!},
                    datasets: [{
                        data: {!! json_encode($statusChartData) !!},
                        backgroundColor: [
                            'rgba(245, 158, 11, 0.7)', // yellow
                            'rgba(59, 130, 246, 0.7)', // blue
                            'rgba(139, 92, 246, 0.7)', // purple
                            'rgba(16, 185, 129, 0.7)', // green
                            'rgba(239, 68, 68, 0.7)' // red
                        ],
                        borderColor: [
                            'rgba(245, 158, 11, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(139, 92, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(239, 68, 68, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Export functionality
            document.getElementById('export-csv').addEventListener('click', function() {
                window.location.href = '{{ route('reports.orders.export', ['format' => 'csv']) }}' + window
                    .location.search;
            });

            document.getElementById('export-pdf').addEventListener('click', function() {
                window.location.href = '{{ route('reports.orders.export', ['format' => 'pdf']) }}' + window
                    .location.search;
            });
        });
    </script>
@endpush
