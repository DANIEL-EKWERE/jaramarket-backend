<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Commission;;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommissionRequest;


class CommissionController extends Controller
{
    public function index()
    {
        $commissions = Commission::paginate(10);
        return view('commissions.index', compact('commissions'));
    }

    public function show($id)
    {
        $commissions = Commission::findOrFail($id);
        
        if (request()->wantsJson()) {
            return response()->json($commissions);
        }

        return view('commisions.show', compact('commission'));
    }
    
    public function store(CommissionRequest $request)
    {
        try {
            $validated = $request->validated();

            $category = Commission::create($validated);

            if ($request->wantsJson()) {
                return response()->json($category, 201);
            }

            return redirect()->back()
                ->with('success', 'Commission created successfully.');
        } catch (Exception $e) {      
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    
    public function update(CommissionRequest $request, $id)
    {
       try {
            $commission = Commission::findOrFail($id);
        
            $commission->update($request->validated());

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Commission updated successfully']);
            }

            return redirect()->back()
                ->with('success', 'Commission updated successfully.');
        } catch (Exception $e) {      
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
                $commission = Commission::findOrFail($id);
                $commission->delete();

                if (request()->wantsJson()) {
                    return response()->json(['message' => 'Commission deleted successfully']);
                }

                return redirect()->back()
                    ->with('success', 'Commission deleted successfully.');
            } catch (Exception $e) {      
                return redirect()->back()
                    ->with('error', $e->getMessage());
            }
    }

    public function create()
    {
        return view('commissions.create');
    }

    public function edit($id)
    {
        $commission = Commission::findOrFail($id);
        return view('commissions.edit', compact('commission'));
    }
}
