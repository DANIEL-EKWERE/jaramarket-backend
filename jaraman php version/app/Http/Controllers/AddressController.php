<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AddressRequest;
use App\Http\Resources\AddressResource;

class AddressController extends Controller
{
    public function index()
    {
        try {
            $addresses = auth()->user()->addresses()->latest()->get();
            return response()->success('Addresses retrieved successfully', AddressResource::collection($addresses));
        } catch (Exception $e) {
            return response()->errorResponse('Failed to fetch addresses', [], 500);
        }
    }

    public function store(AddressRequest $request)
    {
        try {
            $address = DB::transaction(function () use ($request) {
                $user = auth()->user();
    
                // If user marks this new address as default, reset all others
                if ($request->is_default) {
                    $user->addresses()->update(['is_default' => false]);
                }
    
                return $user->addresses()->create($request->validated());
            });

            return response()->success('Address created successfully', new AddressResource($address));
        } catch (Exception $e) {
            return response()->errorResponse($e->getMessage(), [], $e->getCode());
        }
    }

    public function update(AddressRequest $request, Address $address)
    {
        try {
            if ($address->user_id !== auth()->id()) {
                return response()->errorResponse('Unauthorized access to address', [], 403);
            }

            DB::transaction(function () use ($request, $address) {
                if ($request->is_default) {
                    $address->user->addresses()->update(['is_default' => false]);
                }
    
                $address->update($request->validated());
            });

            return response()->success('Address updated successfully', new AddressResource($address));
        } catch (Exception $e) {
            return response()->errorResponse($e->getMessage(), [], $e->getCode());
        }
    }
}