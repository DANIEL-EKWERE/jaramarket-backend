<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice()
    {
        return view('auth.verify-email');
    }

    public function verify(Request $request)
    {
        $user = User::find($request->route('id'));

        if (
            !$user ||
            !hash_equals(sha1($user->getEmailForVerification()), $request->route('hash'))
        ) {
            throw new AuthorizationException;
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended('dashboard');
        }

        $user->markEmailAsVerified();

        return redirect()->intended('dashboard')->with('status', 'Your email has been verified!');
    }

    public function resend(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Verification link sent!');
    }
}
