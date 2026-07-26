<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Response;
use App\Exceptions\GeneralException;
use App\Http\Requests\VendorCategoryRequest;
use App\Http\Resources\VendorCategoryResource;

class VendorCategoryController extends Controller
{
    public function store(VendorCategoryRequest $request, $email)
    {
        try {
            $vendor = User::where('email', $email)->firstOrFail();
            $vendor->categories()->sync($request->category_ids);
            return response()->success('Category added successfully', VendorCategoryResource::collection($vendor->categories), 201);
        } catch (Exception $e) {
          report($e);

           return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
