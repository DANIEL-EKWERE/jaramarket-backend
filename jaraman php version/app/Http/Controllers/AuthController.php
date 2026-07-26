<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Enums\UserPermissionsEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = User::create([
                'firstname'     => $validated['firstname'],
                'lastname'      => $validated['lastname'],
                'email'         => $validated['email'],
                'password'      => $validated['password'],
                'role'          => UserPermissionsEnum::CUSTOMER(),
                'referral_code' => Str::random(10),
            ]);
            event(new Registered($user));
            Auth::logout();
            return redirect()->route('login.show')->with('success', 'Account created. Please login.');
        } catch (Throwable $e) {
            Log::error('Registration failed: '.$e->getMessage());
            return back()->withInput()->withErrors(['register' => 'Something went wrong. Please try again.']);
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email'    => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                $user = Auth::user();

                if (!UserPermissionsEnum::from($user->role)->isAdminRole()) {
                    Auth::logout();
                    return back()->withErrors(['email' => 'Unauthorized. Admin access only.'])->onlyInput('email');
                }
                if (!$user->is_active) {
                    Auth::logout();
                    return back()->withErrors(['email' => 'Your account is inactive.'])->onlyInput('email');
                }

                $user->update(['last_login' => now()]);
                $request->session()->regenerate();
                return redirect()->intended('/dashboard')->with('success', 'Welcome back, '.$user->firstname.'!');
            }
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        } catch (Throwable $e) {
            Log::error('Login failed: '.$e->getMessage());
            return back()->withErrors(['login' => 'Something went wrong.']);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login.show')->with('success', 'Logged out successfully.');
    }

    public function profilePage()
    {
        $user = auth()->user();
        return view('admin.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'firstname'        => ['required', 'string', 'max:100'],
            'lastname'         => ['required', 'string', 'max:100'],
            'email'            => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone_number'     => ['nullable', 'string', 'max:20'],
            'current_password' => ['nullable', 'required_with:new_password'],
            'new_password'     => ['nullable', Password::min(8)],
        ]);

        if (!empty($validated['current_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            $user->password = $validated['new_password'];
        }

        $user->fill([
            'firstname'    => $validated['firstname'],
            'lastname'     => $validated['lastname'],
            'email'        => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
        ])->save();

        return redirect()->route('admin.profile')->with('success', 'Profile updated successfully.');
    }
}
