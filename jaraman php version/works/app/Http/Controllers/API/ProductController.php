<?php

namespace App\Http\Controllers\API;

use App\Enums\CategoryTypeEnum;
use App\Models\Uom;
use App\Models\State;
use App\Models\Product;
use App\Models\Category;
use App\Models\Ingredient;
use App\Http\Resources\UomResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\IngredientResource;
use App\Filters\FoodFilter\Type as FoodFilterType;
use App\Filters\FoodFilter\Name as FoodFilterByName;
use App\Filters\FoodFilter\Search as FoodFilterSearch;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * All products grouped by category.
     * Requires: ?state_id=
     */
    public function getCategoriesAllProducts(Request $request)
    {
        $request->validate(['state_id' => ['required', 'exists:states,id']]);

        $data = Category::with('products.ingredients', 'products.statePrices')
            ->where('category_type_id', CategoryTypeEnum::FOOD())
            ->whereNull('deleted_at')
            ->orderBy('sort_by')
            ->get();

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data'    => CategoryResource::collection($data),
        ]);
    }

    /**
     * Categories with a limited set of products per category.
     * Requires: ?state_id=
     */
    public function getCategoriesLimitProducts(Request $request)
    {
        $stateId = (int) $request->query('state_id') ?: null;
        $lgaId   = (int) $request->query('lga_id')   ?: null;
        if ($request->user()) {
            $stateId = $stateId ?: ($request->user()->state_id ?? null);
            $lgaId   = $lgaId   ?: ($request->user()->lga_id   ?? null);
        }

        $data = Category::with([
            'products' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(8);
            },
            'products.categories',
            'products.statePrices'    => fn ($q) => $stateId ? $q->where('state_id', $stateId) : $q->whereRaw('1=0'),
            'products.ingredients.statePrices' => fn ($q) => $stateId ? $q->where('state_id', $stateId) : $q->whereRaw('1=0'),
            'products.ingredients.lgaPrices'   => fn ($q) => $lgaId   ? $q->where('lga_id',   $lgaId)   : $q->whereRaw('1=0'),
        ])
            ->where('category_type_id', CategoryTypeEnum::FOOD())
            ->whereNull('deleted_at')
            ->orderBy('sort_by')
            ->get();

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data'    => CategoryResource::collection($data),
        ]);
    }

    /**
     * All products (filterable).
     * Requires: ?state_id=
     */
    public function fetchProduct(Request $request)
    {
        $stateId = (int) $request->query('state_id') ?: null;
        if (!$stateId && $request->user()) {
            $stateId = $request->user()->state_id ?? null;
        }

        $products = Product::with([
                'categories',
                'statePrices' => fn ($q) => $stateId ? $q->where('state_id', $stateId) : $q->whereRaw('1=0'),
            ])
            ->filterWithPipeline([
                FoodFilterByName::class,
                FoodFilterSearch::class,
                FoodFilterType::class,
            ])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'message' => 'Products retrieved successfully',
            'data'    => ProductResource::collection($products),
        ]);
    }

    /**
     * Single product by ID.
     * Requires: ?state_id=
     */
    public function getProductById(Request $request, int $id)
    {
        $stateId = (int) $request->query('state_id') ?: null;
        if (!$stateId && $request->user()) {
            $stateId = $request->user()->state_id ?? null;
        }

        $product = Product::with([
            'ingredients',
            'categories',
            'statePrices' => fn ($q) => $stateId ? $q->where('state_id', $stateId) : $q->whereRaw('1=0'),
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Product retrieved successfully',
            'data'    => new ProductResource($product),
        ]);
    }

    // ─── Vendor ───────────────────────────────────────────────────────────────

    public function getVendorCategories()
    {
        $data = Category::with('ingredients')
            ->where('category_type_id', CategoryTypeEnum::VENDOR())
            ->whereNull('deleted_at')
            ->orderBy('sort_by')
            ->get();

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data'    => CategoryResource::collection($data),
        ]);
    }

    // ─── Lookups ──────────────────────────────────────────────────────────────

    /**
     * List all ingredients with location-aware pricing.
     *
     * Query params (all optional — falls back to auth user profile then default price):
     *   ?lga_id=    (int) customer LGA — highest priority price
     *   ?state_id=  (int) customer state — fallback if no LGA price
     *
     * Price fallback chain: LGA price → State price → Default ingredient price
     */
    public function fetchIngredient(Request $request)
    {
        // Resolve location from query or authenticated user profile
        $lgaId   = (int) $request->query('lga_id')   ?: null;
        $stateId = (int) $request->query('state_id') ?: null;

        if (! $lgaId && ! $stateId && $request->user()) {
            $lgaId   = $request->user()->lga_id   ?? null;
            $stateId = $request->user()->state_id ?? null;
        }

        // Eager-load only the price rows needed for this request
        $data = Ingredient::with([
                'category:id,name',
                'lgaPrices'   => fn ($q) => $lgaId   ? $q->where('lga_id',   $lgaId)   : $q->whereRaw('1=0'),
                'statePrices' => fn ($q) => $stateId ? $q->where('state_id', $stateId) : $q->whereRaw('1=0'),
            ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Ingredients retrieved successfully',
            'data'    => IngredientResource::collection($data),
        ]);
    }

    public function fetchUom()
    {
        $data = Uom::orderBy('name')->get();

        return response()->json([
            'message' => 'Units of measurement retrieved successfully',
            'data'    => UomResource::collection($data),
        ]);
    }

    public function fetchStates()
    {
        $data = State::orderBy('name')->get();

        return response()->json([
            'message' => 'States retrieved successfully',
            'data'    => $data->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
        ]);
    }
}
