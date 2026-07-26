<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Resolve state from query or authenticated user's profile
        $stateId = (int) $request->query('state_id') ?: null;

        if (!$stateId && $request->user()) {
            $stateId = $request->user()->state_id ?? null;
        }

        // State → Default price chain
        $pricing = $this->getPriceForLocation($stateId);

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'description'       => $this->description,
            'price'             => $pricing['price'],
            'discount_price'    => $pricing['discount_price'] ?? $this->discount_price ?? null,
            'price_source'      => $pricing['price_source'], // 'state' | 'default'
            'stock'             => $this->stock,
            'preparation_steps' => collect(explode(',', $this->preparation_steps ?? ''))
                                        ->map(fn ($step) => trim($step))
                                        ->filter()
                                        ->values()
                                        ->toArray(),
            'rating'            => $this->rating,
            'image_url'         => get_media_url($this->image_url),
            'ingredients'       => IngredientResource::collection($this->whenLoaded('ingredients')),
            'categories'        => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at'        => $this->created_at->diffForHumans(),
        ];
    }
}
