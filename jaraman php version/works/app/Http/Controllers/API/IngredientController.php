<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    /**
     * All ingredients, state-priced.
     * Requires: ?state_id=
     */
    public function index(Request $request)
    {
        $request->validate(['state_id' => ['required', 'exists:states,id']]);

        $ingredients = Ingredient::with(['category', 'statePrices'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Ingredients retrieved successfully.',
            'data'    => IngredientResource::collection($ingredients),
        ]);
    }

    /**
     * Single ingredient by ID, state-priced.
     * Requires: ?state_id=
     */
    public function show(Request $request, int $id)
    {
        $request->validate(['state_id' => ['required', 'exists:states,id']]);

        $ingredient = Ingredient::with(['category', 'statePrices'])->findOrFail($id);

        return response()->json([
            'message' => 'Ingredient retrieved successfully.',
            'data'    => new IngredientResource($ingredient),
        ]);
    }
}
