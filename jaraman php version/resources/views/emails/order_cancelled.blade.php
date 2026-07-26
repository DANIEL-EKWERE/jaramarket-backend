@extends('emails.layouts.app')
@section('content')   
<p>Hi {{ $user->firstname }},</p>

<h4>Order Update</h4>
<p>{{ $message }}</p>

<table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; margin: 20px 0;">
    <tr style="background: #f8f8f8;">
        <th align="left" style="border: 1px solid #ddd; padding: 8px;">Order reference</th>
        <td style="border: 1px solid #ddd; padding: 8px;">#{{ $order->id }}</td>
    </tr>
    <tr>
        <th align="left" style="border: 1px solid #ddd; padding: 8px;">Order Date</th>
        <td style="border: 1px solid #ddd; padding: 8px;">{{ \Carbon\Carbon::parse($order->created_at)->format('d M, Y H:i A') }}</td>
    </tr>
    <tr style="background: #f8f8f8;">
        <th align="left" style="border: 1px solid #ddd; padding: 8px;">Status</th>
        <td style="border: 1px solid #ddd; padding: 8px; color: red; font-weight: bold;">Cancelled</td>
    </tr>
    <tr>
        <th align="left" style="border: 1px solid #ddd; padding: 8px;">Order Total</th>
        <td style="border: 1px solid #ddd; padding: 8px;">₦{{ number_format($order->total, 2) }}</td>
    </tr>
</table>

<p>If you have any questions, please contact our support team.</p>

<p>Thank you,</p>
<p><strong>{{ company('site_name', config('app.name')) }}</strong></p>
@endsection

