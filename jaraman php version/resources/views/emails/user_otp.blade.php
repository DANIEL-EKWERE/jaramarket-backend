
@extends('emails.layouts.app')

@section('content')
<h3>Login OTP</h3>

<p>Hello {{ $user->firstname }},</p>
<p>Your OTP is shown below:</p>

<table width="100%" cellpadding="10" cellspacing="0" border="0" style="border-collapse: collapse; margin: 20px 0; text-align: center;">
    <tr>
        <td style="border: 1px solid #ddd; padding: 15px; font-size: 22px; font-weight: bold; background: #f8f8f8;">
            {{ $otp }}
        </td>
    </tr>
</table>

<p><strong>Important:</strong> Please do not share this OTP with anyone.</p>

<p>Thanks,</p>
<p><strong>{{ company('site_name', config('app.name')) }} Team</strong></p>
@endsection

