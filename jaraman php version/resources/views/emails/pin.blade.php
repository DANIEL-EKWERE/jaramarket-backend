@extends('emails.layouts.app')

@section('content')
<h3>Hi {{ $user->firstname }},</h3>

@switch($type)
    @case('setup')
        <p>Your transaction PIN has been <strong>successfully set up ✅</strong>.</p>
        <p>You can now use it to authorize secure transactions on {{ company('site_name', config('app.name')) }}.</p>
        @break

    @case('updated')
        <p>Your transaction PIN was <strong>successfully updated 🔄</strong>.</p>
        <p>If you did not make this change, please contact support immediately.</p>
        @break

    @case('reset_request')
        <p>You requested to reset your transaction PIN 🔑.</p>
        <p>Use this token to reset your PIN:</p>
        <h2 style="color:#2d3748;">{{ $token }}</h2>
        <p>This token will expire in <strong>{{ $expiry }} minutes</strong>.</p>
        <p>If you did not request this reset, kindly ignore this email.</p>
        @break

    @case('reset_confirmed')
            <p>Your transaction PIN has been <strong>successfully reset ✅</strong>.</p>
            <p>You can now log in and create a new secure PIN for transactions.</p>
            @break

    @case('token_validated')
            <p>Your PIN token was successfully validated ✅.</p>
            @break

    @case('token_invalid')
            <p>There was an invalid attempt to use your PIN token ⚠️. If this wasn’t you, please secure your account immediately.</p>
            @break

    @case('token_cleared')
            <p>Your PIN token has been <strong>cleared</strong>. You will need to request a new one for future PIN resets.</p>
            @break

            @default
                <p>This is a notification about your transaction PIN.</p>
    @endswitch

<p>Thank you for securing your account,<br>
{{ company('site_name', config('app.name')) }}</p>
@endsection
