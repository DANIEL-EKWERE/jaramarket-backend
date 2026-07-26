@extends('emails.layouts.app')

@section('content')
<h3>Hi {{ $user->firstname }},</h3>

<p>
  @if($type === \App\Enums\WalletTransactionTypeEnum::CREDIT())
    Your wallet has been <strong>credited</strong> with ₦{{ number_format($amount, 2) }}.
  @else
    Your wallet has been <strong>debited</strong> with ₦{{ number_format($amount, 2) }}.
  @endif
</p>

<table width="100%" border="1" cellspacing="0" cellpadding="8" style="border-collapse: collapse;">
  <tr>
    <td><strong>Transaction Type</strong></td>
    <td>{{ ucfirst($type) }}</td>
  </tr>
  <tr>
    <td><strong>Amount</strong></td>
    <td>₦{{ number_format($amount, 2) }}</td>
  </tr>
  <tr>
    <td><strong>Current Balance</strong></td>
    <td>₦{{ number_format($balance, 2) }}</td>
  </tr>
  @if($reference)
  <tr>
    <td><strong>Reference</strong></td>
    <td>{{ $reference }}</td>
  </tr>
  @endif
  @if($remarks)
  <tr>
    <td><strong>Remarks</strong></td>
    <td>{{ $remarks }}</td>
  </tr>
  @endif
</table>

<p>Thank you,<br>{{ company('site_name', config('app.name')) }}</p>
@endsection
