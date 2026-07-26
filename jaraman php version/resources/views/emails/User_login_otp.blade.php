@extends('emails.layouts.app')

@section('content')
<h3>Login OTP</h3>

<p>Hello {{ $firstname }},</p>
<p>Your OTP for the login process is shown below. Please use it to complete your login:</p>

<table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; margin: 20px 0; text-align: center;">
    <tr>
        <td style="border: 1px solid #ddd; padding: 15px; font-size: 20px; font-weight: bold; background: #f8f8f8;">
            {{ $otp }}
        </td>
    </tr>
</table>

<p><strong>Note:</strong> Please do not share this OTP with anyone for security reasons.</p>

<p>Thanks,</p>
<p><strong>{{ company('site_name', config('app.name')) }} Team</strong></p>
@endsection

