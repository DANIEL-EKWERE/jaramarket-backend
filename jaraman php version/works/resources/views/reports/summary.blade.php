@extends('layouts.app')

@section('title', 'Summary Report')

@section('content')
<div class="py-4">
    <div class="mb-5 flex justify-between items-center">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Summary</h2>
            <p class="text-sm text-gray-600">Filter report by date range</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow sm:rounded-lg mb-5 p-4">
        <form id="filter-form" class="flex space-x-4">
            <input type="date" name="start_date" id="start_date" class="border rounded px-3 py-2">
            <input type="date" name="end_date" id="end_date" class="border rounded px-3 py-2">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Filter</button>
        </form>
    </div>

    <!-- Summary Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto px-4 py-4">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th align="left">Description</th>
                        <th align="left">Amount (₦)</th>
                    </tr>
                </thead>
                <tbody id="summary-body"></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    function loadSummary(params = {}) {
        $.get("{{ route('summary.data') }}", params, function(res) {
            if (res.status) {
                let data = res.data;
                let html = `
                    <tr><td>Wallet Balance</td><td>${data.wallet_balance}</td></tr>
                    <tr><td colspan="2"><hr></td></tr>

                    <tr><td>Total Order Amount</td><td>${data.total_orders_amount}</td></tr>
                    <tr><td colspan="2"><hr></td></tr>

                    <tr><td>Transfer to Vendors</td><td>${data.vendor_amount}</td></tr>
                    <tr><td colspan="2"><hr></td></tr>

                    <tr><td>Total Commission</td><td>${data.total_commission}</td></tr>
                    <tr><td colspan="2"><hr></td></tr>

                    <tr><td>Total Referral Bonus</td><td>${data.total_referral_bonus}</td></tr>
                    <tr><td colspan="2"><hr></td></tr>

                    <tr><td>Value Added Tax</td><td>${data.vat}</td></tr>
                    <tr><td colspan="2"><hr></td></tr>

                    <tr><td>Total Service Charge</td><td>${data.service_charge}</td></tr>
                    <tr><td colspan="2"><hr></td></tr>

                    <tr><td>Total Delivery Charge</td><td>${data.delivery_charge}</td></tr>
                    <tr><td colspan="2"><hr></td></tr>

                    <tr><td>Deposits</td><td>${data.total_deposits}</td></tr>
                    <tr><td colspan="2"><hr></td></tr>

                     <tr><td>Withdrawals</td><td>${data.total_transfers}</td></tr>
                    <tr><td colspan="2"><hr></td></tr>
                `;
                $('#summary-body').html(html);
            }
        });
    }

    // Initial load with today's data
    loadSummary();

    // Filter form submit
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        let start_date = $('#start_date').val();
        let end_date = $('#end_date').val();
        loadSummary({ start_date, end_date });
    });
});
</script>
@endpush
