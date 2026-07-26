@extends('emails.layouts.app')

@section('content')
<p>Hello {{ $user->firstname ?? $user->name }},</p>

<h4>Payment Issue Detected</h4>
<p>{{ $message }}</p> {{-- 👈 role-based dynamic message --}}

<table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; margin: 20px 0;">
    <tr style="background: #f8f8f8;">
        <th align="left" style="border: 1px solid #ddd; padding: 8px;">Customer Email</th>
        <td style="border: 1px solid #ddd; padding: 8px;">{{ $email }}</td>
    </tr>
    <tr>
        <th align="left" style="border: 1px solid #ddd; padding: 8px;">Amount</th>
        <td style="border: 1px solid #ddd; padding: 8px;">₦{{ number_format($data['amount'], 2) }}</td>
    </tr>
    <tr style="background: #f8f8f8;">
        <th align="left" style="border: 1px solid #ddd; padding: 8px;">Reference</th>
        <td style="border: 1px solid #ddd; padding: 8px;">{{ $data['reference'] ?? 'N/A' }}</td>
    </tr>
    <tr>
        <th align="left" style="border: 1px solid #ddd; padding: 8px;">Date</th>
        <td style="border: 1px solid #ddd; padding: 8px;">{{ \Carbon\Carbon::now()->format('d M, Y H:i A') }}</td>
    </tr>
</table>

<p>Please review and take necessary action.</p>

<p>Thank you,</p>
<p><strong>{{ company('site_name', config('app.name')) }}</strong></p>
@endsection
