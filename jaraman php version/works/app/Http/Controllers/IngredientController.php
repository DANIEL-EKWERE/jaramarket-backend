<?php

namespace App\Http\Controllers;

use App\Http\Requests\IngredientRequest;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\State;
use App\Models\Uom;
use App\Services\IngredientService;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class IngredientController extends Controller
{
    public function index()
    {
        return view('ingredients.index');
    }

    public function getData(Request $request)
    {
        $query = Ingredient::with('category')->select('ingredients.*');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->stock === 'in_stock') {
            $query->where('stock', '>', 10);
        } elseif ($request->stock === 'low_stock') {
            $query->whereBetween('stock', [1, 10]);
        } elseif ($request->stock === 'out_of_stock') {
            $query->where('stock', 0);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('raw_stock', fn ($i) => $i->stock)
            ->addColumn('image', fn ($i) => $i->image_url ? get_media_url($i->image_url) : null)
            ->addColumn('category_name', fn ($i) => $i->category?->name ?? '—')
            ->editColumn('price', function ($i) {
                if ($i->discounted_price) {
                    return "<span class='text-green-600 font-semibold'>₦" . number_format($i->discounted_price, 2) . "</span>
                            <span class='line-through text-gray-400 ml-1 text-xs'>₦" . number_format($i->price, 2) . "</span>";
                }
                return '<span class="font-medium">₦' . number_format($i->price, 2) . '</span>';
            })
            ->editColumn('stock', function ($i) {
                if ($i->stock > 10) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-green-50 text-green-700 border border-green-100">' . $i->stock . ' in stock</span>';
                } elseif ($i->stock > 0) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-amber-50 text-amber-700 border border-amber-100">Low: ' . $i->stock . '</span>';
                }
                return '<span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-red-50 text-red-600 border border-red-100">Out of stock</span>';
            })
            ->addColumn('actions', function ($i) {
                $editUrl = route('ingredients.edit', $i);
                return '
                <div class="flex items-center justify-center gap-1">
                    <a href="' . $editUrl . '"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-green-50 hover:text-green-700 text-gray-600 text-xs font-medium rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                    <button type="button"
                            class="delete-ingredient inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-600 text-xs font-medium rounded-lg transition"
                            data-ingredient-id="' . $i->id . '"
                            data-ingredient-name="' . addslashes($i->name) . '">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                </div>';
            })
            ->rawColumns(['price', 'stock', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $units      = Uom::orderBy('name')->get();
        $states     = State::orderBy('name')->get();
        $lgas       = \App\Models\Lga::with('state')->orderBy('name')->get();

        return view('ingredients.create', compact('categories', 'units', 'states', 'lgas'));
    }

    public function store(IngredientRequest $request)
    {
        try {
            app(IngredientService::class)->create($request->validated());
            return redirect()->route('ingredients.index')->with('success', 'Ingredient created successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Ingredient $ingredient)
    {
        $categories = Category::orderBy('name')->get();
        $units      = Uom::orderBy('name')->get();
        $states     = State::orderBy('name')->get();
        $lgas       = \App\Models\Lga::with('state')->orderBy('name')->get();

        $ingredient->load(['statePrices.state', 'lgaPrices.lga.state', 'category']);

        return view('ingredients.edit', compact('ingredient', 'categories', 'units', 'states', 'lgas'));
    }

    public function update(IngredientRequest $request, Ingredient $ingredient)
    {
        try {
            app(IngredientService::class)->update($request->validated(), $ingredient);
            return redirect()->back()->with('success', 'Ingredient updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(Ingredient $ingredient)
    {
        try {
            app(IngredientService::class)->delete($ingredient);
            return response()->json(['success' => true, 'message' => 'Ingredient deleted successfully.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
