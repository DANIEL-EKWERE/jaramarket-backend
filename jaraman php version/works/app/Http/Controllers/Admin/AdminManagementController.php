<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserPermissionsEnum;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\State;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AdminManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin', 'permission:manage_admins']);
    }

    public function index(Request $request)
    {
        $admins = User::admins()
            ->with(['state', 'userPermissions'])
            ->when($request->search, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('firstname', 'like', "%{$request->search}%")
                   ->orWhere('lastname',  'like', "%{$request->search}%")
                   ->orWhere('email',     'like', "%{$request->search}%")
            ))
            ->when($request->role,     fn ($q) => $q->where('role',     $request->role))
            ->when($request->state_id, fn ($q) => $q->where('state_id', $request->state_id))
            ->latest()->paginate(15)->withQueryString();

        $roles  = UserPermissionsEnum::adminRoles();
        $states = State::orderBy('name')->get();
        return view('admin.roles.index', compact('admins', 'roles', 'states'));
    }

    public function create()
    {
        $roles       = UserPermissionsEnum::adminRoles();
        $states      = State::orderBy('name')->get();
        $permissions = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        $adminPermIds = [];
        return view('admin.roles.create', compact('roles', 'states', 'permissions', 'adminPermIds'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'firstname'     => ['required', 'string', 'max:100'],
            'lastname'      => ['nullable', 'string', 'max:100'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'phone_number'  => ['nullable', 'string', 'max:20'],
            'role'          => ['required', 'in:'.implode(',', UserPermissionsEnum::adminRoles())],
            'state_id'      => ['nullable', 'exists:states,id'],
            'password'      => ['required', Password::min(8)],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $user = User::create([
            'firstname'         => $validated['firstname'],
            'lastname'          => $validated['lastname'] ?? '',
            'email'             => $validated['email'],
            'phone_number'      => $validated['phone_number'] ?? null,
            'role'              => $validated['role'],
            'state_id'          => $validated['state_id'] ?? null,
            'password'          => $validated['password'],
            'is_active'         => true,
            'email_verified_at' => now(),
            'referral_code'     => Str::random(10),
        ]);

        Wallet::firstOrCreate(['user_id' => $user->id]);

        if (!empty($validated['permissions'])) {
            $user->userPermissions()->sync($validated['permissions']);
        } else {
            $user->syncDefaultPermissions();
        }

        return redirect()->route('admin-management.index')->with('success', 'Admin created successfully.');
    }

    public function edit(User $admin)
    {
        $roles        = UserPermissionsEnum::adminRoles();
        $states       = State::orderBy('name')->get();
        $permissions  = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        $adminPermIds = $admin->userPermissions->pluck('id')->toArray();
        return view('admin.roles.create', compact('admin', 'roles', 'states', 'permissions', 'adminPermIds'));
    }

    public function update(Request $request, User $admin)
    {
        $validated = $request->validate([
            'firstname'     => ['required', 'string', 'max:100'],
            'lastname'      => ['nullable', 'string', 'max:100'],
            'email'         => ['required', 'email', 'unique:users,email,'.$admin->id],
            'phone_number'  => ['nullable', 'string', 'max:20'],
            'role'          => ['required', 'in:'.implode(',', UserPermissionsEnum::adminRoles())],
            'state_id'      => ['nullable', 'exists:states,id'],
            'password'      => ['nullable', Password::min(8)],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $admin->fill([
            'firstname'    => $validated['firstname'],
            'lastname'     => $validated['lastname'] ?? '',
            'email'        => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'role'         => $validated['role'],
            'state_id'     => $validated['state_id'] ?? null,
        ]);
        if (!empty($validated['password'])) $admin->password = $validated['password'];
        $admin->save();

        if (isset($validated['permissions'])) {
            $admin->userPermissions()->sync($validated['permissions']);
        } else {
            $admin->syncDefaultPermissions();
        }

        return redirect()->route('admin-management.index')->with('success', 'Admin updated successfully.');
    }

    public function toggleStatus(User $admin)
    {
        if ($admin->id === auth()->id()) return back()->with('error', 'You cannot deactivate yourself.');
        $admin->update(['is_active' => !$admin->is_active]);
        return back()->with('success', 'Admin '.($admin->is_active ? 'activated' : 'deactivated').' successfully.');
    }

    public function destroy(User $admin)
    {
        if ($admin->id === auth()->id()) return back()->with('error', 'You cannot delete yourself.');
        $admin->delete();
        return redirect()->route('admin-management.index')->with('success', 'Admin deleted.');
    }

    public function resetPermissions(User $admin)
    {
        $admin->syncDefaultPermissions();
        return back()->with('success', 'Permissions reset to role defaults.');
    }
}
