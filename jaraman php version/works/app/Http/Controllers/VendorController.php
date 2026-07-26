<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\Order;
use Exception;
use App\Enums\UserPermissionsEnum;
use App\Http\Requests\UserProfileRequest;
use App\Models\Vendor;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('vendors.index');
    }

    public function getData(Request $request)
    {
        $query = User::where('role', UserPermissionsEnum::VENDOR());

        $query->when($request->status, function ($q) use ($request) {
            $q->where('is_active', $request->status === 'active' ? 1 : 0);
        }, function ($q) {
            $q->whereDate('created_at', now()->toDateString());
        });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($sub) use ($search) {
                $sub->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%")
                    ->orWhere('business_address', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('created_at', fn($vendor) => $vendor->created_at->format('M d, Y H:i'))
            ->editColumn('status', function ($vendor) {
                return $vendor->is_active
                    ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>'
                    : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>';
            })
            ->addColumn('actions', function ($vendor) {
                return '
                    <a href="'.route('vendors.edit', $vendor).'" class="text-green-600 hover:text-green-900 mr-3">Edit</a>
                    <button type="button" class="text-red-600 hover:text-red-900 delete-vendor" data-vendor-id="'.$vendor->id.'">Delete</button>
                ';
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('vendors.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserProfileRequest $request)
    {
        try {
            $data = $request->validated();
            $user = User::create([
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserPermissionsEnum::VENDOR(),
                'business_name' => $data['business_name'],
                'business_address' => $data['business_address'],
                'is_active' => true,
                'bank_name' => $data['bank_name'] ?? null,
                'account_number' => $validatadated['account_number'] ?? null,
                'account_name' => $data['account_name'] ?? null,
            ]);

            Wallet::create(['user_id' => $user->id]);
        
            return redirect()->back()
                ->with('success', 'Vendor created successfully');

        } catch (Exception $e) {      
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        $orders = Order::whereHas('items', function ($query) use ($user) {
            $query->where('vendor_id', $user->id);
        })->with(['items.product', 'items.vendor'])
          ->get();
        
        if (request()->wantsJson()) {
            return response()->json($user);
        }
        return view('vendors.edit', compact(['user','orders']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserProfileRequest $request, User $user)
    {
        $data = $request->validated();
        $user->update($data);

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $vendor)
    {
        $vendor->delete();
        return redirect()->route('vendors.index')
            ->with('success', 'Vendor deleted successfully');
    }

    public function toggleStatus(Vendor $vendor)
    {
        $vendor->is_active = !$vendor->is_active;
        $vendor->save();

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor status updated successfully');
    }

    public function toggleVerification(Vendor $vendor)
    {
        $vendor->is_verified = !$vendor->is_verified;
        $vendor->save();

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor verification status updated successfully');
    }
}
