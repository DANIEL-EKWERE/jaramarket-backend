<?php

namespace App\Http\Controllers;

use App\Models\Franchise;
use App\Models\User;
use Illuminate\Http\Request;

class FranchiseController extends Controller
{
    public function index()
    {
        $franchises = Franchise::with('owner')->latest()->paginate(10);
        return view('franchises.index', compact('franchises'));
    }

    public function create()
    {
        $users = User::all();
        return view('franchises.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', 'exists:users,id'],
        ]);

        Franchise::create($validated);

        return redirect()->route('franchises.index')
            ->with('success', 'Franchise created successfully');
    }

    public function edit(Franchise $franchise)
    {
        $users = User::all();
        return view('franchises.edit', compact('franchise', 'users'));
    }

    public function update(Request $request, Franchise $franchise)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', 'exists:users,id'],
        ]);

        $franchise->update($validated);

        return redirect()->route('franchises.index')
            ->with('success', 'Franchise updated successfully');
    }

    public function destroy(Franchise $franchise)
    {
        $franchise->delete();

        return redirect()->route('franchises.index')
            ->with('success', 'Franchise deleted successfully');
    }
}
