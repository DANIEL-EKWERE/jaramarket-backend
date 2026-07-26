<?php

namespace App\Http\Controllers;

use App\Models\PaymentLog;
use Illuminate\Http\Request;

class PaymentReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $payments = PaymentLog::whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->with('owner')
            ->latest()
            ->get();

        $totalPayments = $payments->sum('amount');
        $paymentsByStatus = $payments->groupBy('status')->map(fn($s) => $s->count());

        return view('payments.index', compact('payments','totalPayments','paymentsByStatus','startDate','endDate'));
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $payments = PaymentLog::whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->with('owner')
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payments-report.csv"',
        ];

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Reference', 'Customer', 'Amount', 'Status']);

            foreach ($payments as $payment) {
                $amt = number_format($payment->amount);
                fputcsv($file, [
                    $payment->txn_ref,
                    $payment->created_at->format('Y-m-d H:i:s'),
                    $payment->owner->name,
                    $amt,
                    $payment->status,                    
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
