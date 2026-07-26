<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Food;

/**
 * @OA\Info(title="JaraMarket API", version="1.0")
 * @OA\Server(url="http://localhost:8000")
 * @OA\Tag(
 *     name="Foods",
 *     description="API Endpoints for managing food items"
 * )
 */
class FoodController extends Controller
{
    public function index()
    {
        return Food::with(['ingredients', 'steps'])->get();
    }

    public function store(Request $request)
    {
        $food = Food::create($request->only('name', 'description'));

        foreach ($request->ingredients as $ingredient) {
            $food->ingredients()->create($ingredient);
        }

        foreach ($request->steps as $step) {
            $food->steps()->create($step);
        }

        return response()->json($food, 201);
    }

    public function show($id)
    {
        return Food::with(['ingredients', 'steps'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $food = Food::findOrFail($id);
        $food->update($request->only('name', 'description'));

        if ($request->has('ingredients')) {
            $food->ingredients()->delete();
            foreach ($request->ingredients as $ingredient) {
                $food->ingredients()->create($ingredient);
            }
        }

        if ($request->has('steps')) {
            $food->steps()->delete();
            foreach ($request->steps as $step) {
                $food->steps()->create($step);
            }
        }

        return response()->json(['message' => 'Food item updated successfully']);
    }

    public function destroy($id)
    {
        $food = Food::findOrFail($id);
        $food->ingredients()->delete();
        $food->steps()->delete();
        $food->delete();

        return response()->json(['message' => 'Food item deleted successfully']);
    }
}
