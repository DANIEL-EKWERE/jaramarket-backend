<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Enums\UserPermissionsEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index()
    {
        $admins = User::where('role', 'admin')->latest()->paginate(10);
        return view('admin.index', compact('admins'));
    }

    public function create()
    {
        return view('admin.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
        ]);

        $user = User::create([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => UserPermissionsEnum::ADMIN(),
            'phone_number' => $validated['phone'],
            'is_active' => true,
            'email_verified_at' => now(),
            'referral_code' => Str::random(10),
        ]);

        Wallet::create(['user_id' => $user->id]);

        return redirect()->route('admin.index')
            ->with('success', 'Admin created successfully');
    }

    public function show(User $admin)
    {
        return view('admin.show', compact('admin'));
    }

    public function edit(User $admin)
    {
        return view('admin.edit', compact('admin'));
    }

    public function update(Request $request, User $admin)
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $admin->id],
            'password' => ['nullable', Password::defaults()],
        ]);

        $admin->firstname = $validated['firstname'];
        $admin->lastname = $validated['lastname'];
        $admin->email = $validated['email'];
        $admin->phone_number = $validated['phone_number'];

        if (!empty($validated['password'])) {
            $admin->password = $validated['password'];
        }

        $admin->save();

        return redirect()->route('admin.index')
            ->with('success', 'Admin updated successfully');
    }

    public function destroy(User $admin)
    {
        $admin->delete();
        return redirect()->route('admin.index')
            ->with('success', 'Admin deleted successfully');
    }

    public function toggleStatus(User $admin)
    {
        $admin->is_active = !$admin->is_active;
        $admin->save();

        return redirect()->route('admin.index')
            ->with('success', 'Admin status updated successfully');
    }

    public function profile()
    {
        $admin = auth()->user();
        return view('admin.profile', compact('admin'));
    }

    public function updateProfile(Request $request)
    {
        $admin = auth()->user();

        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $admin->id],
            'current_password' => ['nullable', 'required_with:new_password'],
            'new_password' => ['nullable', Password::defaults()],
        ]);

        if (!empty($validated['current_password'])) {
            if (!Hash::check($validated['current_password'], $admin->password)) {
                return back()->withErrors(['current_password' => 'The current password is incorrect.']);
            }
            $admin->password = $validated['new_password'];
        }

        $admin->firstname = $validated['firstname'];
        $admin->lastname = $validated['lastname'];
        $admin->email = $validated['email'];
        $admin->save();

        return redirect()->route('admin.profile')
            ->with('success', 'Profile updated successfully');
    }
}
