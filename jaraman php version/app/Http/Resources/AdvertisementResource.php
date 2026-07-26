<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_url' => $this->image ? asset($this->image) : null,
            'type' => $this->type,
            'value' => $this->value,
            'ingredients' => $this->ingredients->map(function ($ingredient) {
                                return [
                                    'name' => $ingredient->name,
                                    'price' => $ingredient->price,
                                    'discounted_price' => $ingredient->discounted_price,
                                ];
                            }),
            'status' => $this->status,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
