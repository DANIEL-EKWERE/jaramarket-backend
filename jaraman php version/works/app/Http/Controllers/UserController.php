<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Enums\UserPermissionsEnum;
use App\Http\Requests\UserProfileRequest;
use Yajra\DataTables\Facades\DataTables;
use App\Models\User;
use Illuminate\Support\Str;
use Exception;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index');
    }

    public function getData(Request $request)
    {
        $query = User::where('role', UserPermissionsEnum::CUSTOMER())
            ->withCount('orders'); // needed for stat-ordered and orders_count column

        // FIX 1: Only apply the "today only" default when loading the TABLE
        //         (normal paginated requests). The stats fetch sends stats=1 to
        //         explicitly request ALL records with no date restriction.
        $isStatsFetch = $request->boolean('stats');

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('is_active', $request->status === 'active' ? 1 : 0);
        }, function ($q) use ($isStatsFetch) {
            // Only restrict to today when it's a normal table load, not the stats call
            if (!$isStatsFetch) {
                $q->whereDate('created_at', now()->toDateString());
            }
        });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($sub) use ($search) {
                $sub->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname',     'like', "%{$search}%")
                    ->orWhere('email',        'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()

            // FIX 2: Add raw_active so the blade loadStats() can count active/inactive
            ->addColumn('raw_active', fn($user) => (int) $user->is_active)

            // FIX 3: Expose orders_count (already eager-loaded via withCount)
            ->addColumn('orders_count', fn($user) => $user->orders_count ?? 0)

            // FIX 4: Add full_name so the delete button can carry data-name properly
            ->addColumn('full_name', fn($user) => trim($user->firstname . ' ' . $user->lastname))

            ->editColumn('created_at', fn($user) => $user->created_at->format('d M Y H:i'))

            ->editColumn('status', function ($user) {
                return $user->is_active
                    ? '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active</span>'
                    : '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border bg-red-50 text-red-600 border-red-200"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Inactive</span>';
            })

            ->addColumn('actions', function ($user) {
                $editUrl  = route('users.edit', $user);
                $fullName = addslashes(trim($user->firstname . ' ' . $user->lastname));
                return '
                    <div class="flex items-center justify-end gap-2">
                        <a href="' . $editUrl . '"
                           class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 hover:border-slate-300 transition-colors">
                            Edit
                        </a>
                        <button type="button"
                                class="delete-user inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors"
                                data-user-id="' . $user->id . '"
                                data-name="' . $fullName . '">
                            Delete
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(UserProfileRequest $request)
    {
        try {
            $data = $request->validated();

            $user = User::create([
                'firstname'     => $data['firstname'],
                'lastname'      => $data['lastname'],
                'email'         => $data['email'],
                'password'      => $data['password'],
                'role'          => $data['role'],
                'referral_code' => Str::random(10),
            ]);

            Wallet::create(['user_id' => $user->id]);

            return redirect()->back()->with('success', 'User created successfully');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(UserProfileRequest $request, User $user)
    {
        try {
            $data = $request->validated();

            $userData = [
                'firstname' => $data['firstname'],
                'lastname'  => $data['lastname'],
                'is_active' => $data['is_active'],
            ];

            if (!empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            $user->update($userData);

            return redirect()->back()->with('success', 'User updated successfully');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'Activated' : 'Deactivated';
        return redirect()->route('users.index')->with('success', "User {$status} successfully");
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();
            return redirect()->back()->with('success', 'User deleted successfully');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
