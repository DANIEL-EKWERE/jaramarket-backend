<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\State;
use App\Models\Uom;
use App\Services\FoodService;
use App\Enums\CategoryTypeEnum;
use Exception;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index');
    }

    public function getData(Request $request)
    {
        $query = Product::with('categories')->select('products.*');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $request->category));
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
            ->addColumn('image', fn ($p) => $p->image_url ? get_media_url($p->image_url) : null)
            ->addColumn('categories', fn ($p) => $p->categories->map(fn ($c) => ['name' => $c->name])->toArray())
            ->editColumn('price', function ($p) {
                if ($p->discount_price) {
                    return "<span class='text-green-600 font-semibold'>₦" . number_format($p->discount_price, 2) . "</span>
                            <span class='line-through text-gray-400 ml-1'>₦" . number_format($p->price, 2) . "</span>";
                }
                return '₦' . number_format($p->price, 2);
            })
            ->editColumn('stock', function ($p) {
                if ($p->stock > 10) {
                    return '<span class="badge-stock-in">' . $p->stock . ' in stock</span>';
                } elseif ($p->stock > 0) {
                    return '<span class="badge-stock-low">Low: ' . $p->stock . '</span>';
                }
                return '<span class="badge-stock-out">Out of stock</span>';
            })
            ->addColumn('raw_stock', fn ($p) => $p->stock)
            ->addColumn('actions', function ($p) {
                $editUrl = route('products.edit', $p);
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
                                class="delete-product inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-600 text-xs font-medium rounded-lg transition"
                                data-product-id="' . $p->id . '"
                                data-product-name="' . addslashes($p->name) . '">
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
        $categories = Category::where('category_type_id', CategoryTypeEnum::FOOD())->get();
        $ingredients = Ingredient::orderBy('name')->get();
        $uoms = Uom::orderBy('name')->get();
        $states = State::orderBy('name')->get();

        return view('products.create', compact('categories', 'ingredients', 'uoms', 'states'));
    }

    public function store(ProductRequest $request)
    {
        try {
            app(FoodService::class)->create($request->validated());
            return redirect()->route('products.index')->with('success', 'Product created successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Product $product)
    {
        $categories = Category::where('category_type_id', CategoryTypeEnum::FOOD())->get();
        $ingredients = Ingredient::orderBy('name')->get();
        $uoms = Uom::orderBy('name')->get();
        $states = State::orderBy('name')->get();

        $product->load(['ingredients', 'categories', 'statePrices.state']);

        return view('products.edit', compact('product', 'categories', 'ingredients', 'uoms', 'states'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        try {
            app(FoodService::class)->update($request->validated(), $product);
            return redirect()->back()->with('success', 'Product updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        try {
            app(FoodService::class)->delete($product);
            return response()->json(['success' => true, 'message' => 'Product deleted successfully.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
