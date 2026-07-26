@extends('emails.layouts.app')

@section('content')
<h3>
  @switch($recipientType)
    @case(\App\Enums\OrderNotificationTypeEnum::CUSTOMER())
      Hi {{ $user->firstname }},
      @break
    @case(\App\Enums\OrderNotificationTypeEnum::VENDOR())
      Dear Vendor {{ $user->firstname }},
      @break
    @case(\App\Enums\OrderNotificationTypeEnum::ADMIN())
      Dear Admin {{ $user->firstname }},
      @break
  @endswitch
</h3>

{{-- Messages --}}
@if($recipientType === \App\Enums\OrderNotificationTypeEnum::CUSTOMER())
  @if($status === \App\Enums\StatusEnum::PROCESSING())
    <p>Your order <strong>#{{ $order->reference }}</strong> is now being processed.</p>
  @elseif($status === \App\Enums\StatusEnum::COMPLETED())
    <p>Your order <strong>#{{ $order->reference }}</strong> is ready for delivery.</p>
  @endif
@endif

@if($recipientType === \App\Enums\OrderNotificationTypeEnum::VENDOR() && $status === \App\Enums\StatusEnum::PROCESSING())
  <p>You have accepted items from order <strong>#{{ $order->reference }}</strong>.</p>
@endif

@if($recipientType === \App\Enums\OrderNotificationTypeEnum::ADMIN())
  <p>Order <strong>#{{ $order->reference }}</strong> had a status update: <strong>{{ ucfirst($status) }}</strong>.</p>
@endif

{{-- Order Items Table --}}
<h4>Order Details</h4>
<table width="100%" border="1" cellspacing="0" cellpadding="8" style="border-collapse: collapse;">
  <thead style="background-color: #f3f3f3;">
    <tr>
      <th align="left">Item</th>
      <th align="center">Qty</th>
      @if(in_array($recipientType, [
        \App\Enums\OrderNotificationTypeEnum::ADMIN(),
        \App\Enums\OrderNotificationTypeEnum::VENDOR()
      ]))
        <th align="right">Vendor</th>
      @endif
      <th align="right">Price (₦)</th>
      <th align="right">Total (₦)</th>
    </tr>
  </thead>
  <tbody>
    @foreach($order->items as $item)
      @if($recipientType === \App\Enums\OrderNotificationTypeEnum::VENDOR() && $item->vendor_id !== $user->id)
        @continue
      @endif
      <tr>
        <td>{{ $item->product?->name ?? $item->ingredient?->name }}</td>
        <td align="center">{{ $item->quantity }}</td>
        @if(in_array($recipientType, [
          \App\Enums\OrderNotificationTypeEnum::ADMIN(),
          \App\Enums\OrderNotificationTypeEnum::VENDOR()
        ]))
          <td align="right">{{ $item->vendor?->name ?? 'N/A' }}</td>
        @endif
        <td align="right">{{ number_format($item->price, 2) }}</td>
        <td align="right">{{ number_format($item->price * $item->quantity, 2) }}</td>
      </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="{{ in_array($recipientType, [\App\Enums\OrderNotificationTypeEnum::ADMIN(), \App\Enums\OrderNotificationTypeEnum::VENDOR()]) ? 4 : 3 }}" align="right">
        <strong>Total</strong>
      </td>
      <td align="right"><strong>₦{{ number_format($order->total, 2) }}</strong></td>
    </tr>
  </tfoot>
</table>

{{-- Vendor Activity Logs - Only for Admin --}}
@if($recipientType === \App\Enums\OrderNotificationTypeEnum::ADMIN() && isset($order->items))
  <h4>Vendor Activity Logs</h4>
  <table width="100%" border="1" cellspacing="0" cellpadding="8" style="border-collapse: collapse;">
    <thead style="background-color: #f3f3f3;">
      <tr>
        <th align="left">Vendor</th>
        <th align="left">Item</th>
        <th align="center">Qty</th>
        <th align="left">Status</th>
        <th align="left">Updated At</th>
      </tr>
    </thead>
    <tbody>
      @foreach($order->items as $item)
        <tr>
          <td>{{ $item->vendor?->firstname ?? 'N/A' }}</td>
          <td>{{ $item->product?->name ?? $item->ingredient?->name}}</td>
          <td align="center">{{ $item->quantity }}</td>
          <td>{{ ucfirst($item->status) }}</td>
          <td>{{ \Carbon\Carbon::parse($item->vendor_at)->toDayDateTimeString() ?? 'N/A' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
@endif

<p>Thank you,<br>{{ company('site_name', config('app.name')) }}</p>
@endsection
