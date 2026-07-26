<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngredientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Resolve location IDs from the request
        // Priority: lga_id from query → state_id from query → user profile
        $lgaId   = (int) $request->query('lga_id')   ?: null;
        $stateId = (int) $request->query('state_id') ?: null;

        // If not in query, try authenticated user's stored location
        if (! $lgaId && ! $stateId && $request->user()) {
            $lgaId   = $request->user()->lga_id   ?? null;
            $stateId = $request->user()->state_id ?? null;
        }

        // Resolve effective price with full fallback chain: LGA → State → Default
        $pricing = $this->getPriceForLocation($lgaId, $stateId);

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'description'      => $this->description,
            'price'            => $pricing['price'],
            'discounted_price' => $pricing['discounted_price'],
            'price_source'     => $pricing['price_source'], // 'lga' | 'state' | 'default'
            'unit'             => $this->unit,
            'stock'            => $this->stock,
            'category'         => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'image_url'        => get_media_url($this->image_url),
            'products'         => ProductResource::collection($this->whenLoaded('products')),
            'created_at'       => $this->created_at->diffForHumans(),
        ];
    }
}
