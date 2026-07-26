<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'firstname' => ['required', 'string', 'max:255'],
                'lastname'  => ['required', 'string', 'max:255'],
                'email'     => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'current_password' => [
                    'nullable',
                    'required_with:password',
                    function ($attribute, $value, $fail) use ($user) {
                        if (!Hash::check($value, $user->password)) {
                            $fail('The current password is incorrect.');
                        }
                    }
                ],
                'password'  => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);

            $userData = [
                'firstname' => $validated['firstname'],
                'lastname'  => $validated['lastname'],
                'email'     => $validated['email'],
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $user->update($userData);

            return redirect()->route('profile.index')
                ->with('success', 'Profile updated successfully');
        } catch (\Exception $e) {
            Log::error('Profile update failed: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Something went wrong while updating your profile.');
        }
    }
}
