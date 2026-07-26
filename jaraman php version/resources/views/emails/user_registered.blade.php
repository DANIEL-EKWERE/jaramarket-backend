
@extends('emails.layouts.app')

@section('content')
<h3>Welcome to {{ company('site_name', config('app.name')) }}</h3>

<p>Hi {{ $user->firstname }},</p>

<p>Thank you for registering with us. Your account has been created successfully.</p>

<p><strong>Please keep your password safe and do not share it with anyone.</strong></p>

<p>We’re excited to have you on board!</p>

<p>Best regards,</p>
<p><strong>{{ company('site_name', config('app.name')) }} Team</strong></p>
@endsection

