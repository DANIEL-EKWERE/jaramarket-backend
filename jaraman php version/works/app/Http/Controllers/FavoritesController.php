<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Favorite;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FavoriteRequest;
use App\Http\Resources\FavoriteResource;

class FavoritesController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $favorites = Favorite::where('user_id', $user->id)
            ->get();
            
        return response()->json([
            'status' => true,
            'message' => "Favorites retrieved successfully",
            'data' => FavoriteResource::collection($favorites)
        ]);
    }

    public function store(FavoriteRequest $request)
    {
        try {
            $user = Auth::user();
            // Check if already favorited
            $exists = Favorite::where('user_id', $user->id)
                ->where('product_id', $request->product_id)
                ->exists();
    
            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Product already in favorites'
                ], 400);
            }
    
            $favorite = Favorite::create([
                'user_id' => $user->id,
                'product_id' => $request->product_id,
            ]);
    
            return response()->json([
                'status' => true,
                'message' => 'Product added to favorites',
                'data' => new FavoriteResource($favorite)
            ], 201);
    
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $favorite = Favorite::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$favorite) {
            return response()->json([
                'status' => false,
                'message' => 'Favorite not found'
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'status' => true,
            'message' => 'Favorite removed successfully'
        ]);
    }
} 