
@extends('emails.layouts.app')

@section('content')
<p>Hi {{ $user->firstname }},</p>

<h4>Order Confirmation</h4>
<p>Your order has been successfully placed. 🎉</p>

<table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; margin: 20px 0;">
    <tr style="background: #f8f8f8;">
        <th align="left" style="border: 1px solid #ddd; padding: 8px;">Reference</th>
        <td style="border: 1px solid #ddd; padding: 8px;">{{ $order->reference }}</td>
    </tr>
    <tr>
        <th align="left" style="border: 1px solid #ddd; padding: 8px;">Order Total</th>
        <td style="border: 1px solid #ddd; padding: 8px;">₦{{ number_format($order->total, 2) }}</td>
    </tr>
    <tr style="background: #f8f8f8;">
        <th align="left" style="border: 1px solid #ddd; padding: 8px;">Placed On</th>
        <td style="border: 1px solid #ddd; padding: 8px;">{{ \Carbon\Carbon::parse($order->order_date)->format('d M, Y H:i A') }}</td>
    </tr>
</table>

<p>You can log in to your account to view full order details and track progress.</p>

<p>Thanks for your patronage 🙏</p>

<p><strong>{{ company('site_name', config('app.name')) }}</strong></p>
@endsection

