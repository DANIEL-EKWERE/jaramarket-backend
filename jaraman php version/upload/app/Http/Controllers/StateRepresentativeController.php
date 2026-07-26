<?php

namespace App\Http\Controllers;

use App\Models\StateRepresentative;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StateRepresentativeController extends Controller
{
    public function index()
    {
        $representatives = StateRepresentative::with('user')->latest()->paginate(10);
        return view('representatives.index', compact('representatives'));
    }

    public function create()
    {
        $users = User::get();
        return view('representatives.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['required', 'string', 'max:20'],
            'state' => ['required', 'string', 'max:255'],
            'lga' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
        ]);

        // Create a new user with STATE_REPRESENTATIVE role
        $user = User::create([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(12)), // Generate a random password
            'role' => 'STATE_REPRESENTATIVE',
            'referral_code' => Str::random(10)
        ]);

        // Create the state representative
        $representative = StateRepresentative::create([
            'user_id' => $user->id,
            'phone' => $validated['phone'],
            'state' => $validated['state'],
            'lga' => $validated['lga'],
            'address' => $validated['address']
        ]);

        return redirect()->route('representatives.index')
            ->with('success', 'State Representative created successfully');
    }

    public function edit(StateRepresentative $representative)
    {
        $users = User::where('is_active', true)->get();
        return view('representatives.edit', compact('representative', 'users'));
    }

    public function update(Request $request, StateRepresentative $representative)
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $representative->user->id],
            'phone' => ['required', 'string', 'max:20'],
            'state' => ['required', 'string', 'max:255'],
            'lga' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
        ]);

        // Update the associated user
        $user = $representative->user;
        if ($user) {
            $user->update([
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'email' => $validated['email']
            ]);
        }

        $representative->update([
            'phone' => $validated['phone'],
            'state' => $validated['state'],
            'lga' => $validated['lga'],
            'address' => $validated['address']
        ]);

        return redirect()->route('representatives.index')
            ->with('success', 'State Representative updated successfully');
    }

    public function destroy(StateRepresentative $representative)
    {
        // Delete the associated user
        if ($representative->user) {
            $representative->user->delete();
        }
        
        $representative->delete();
        return redirect()->route('representatives.index')
            ->with('success', 'State Representative deleted successfully');
    }

    public function toggleStatus(StateRepresentative $representative)
    {
        $representative->is_active = !$representative->is_active;
        $representative->save();

        return redirect()->route('representatives.index')
            ->with('success', 'Representative status updated successfully');
    }
}
