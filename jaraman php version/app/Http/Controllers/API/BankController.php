<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\BankResource;
use App\Models\Bank;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function index(Request $request)
    {
        $query = Bank::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $banks = $query->orderBy('name')->get();

        return response()->success('Banks retrieved successfully', BankResource::collection($banks), 200);
    }
}
