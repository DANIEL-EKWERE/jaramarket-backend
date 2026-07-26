<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngredientOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'item_id'    => $this->id,
            'customer'   => $this->order->user->name,
            'order_id'   => $this->order->id,
            'order_no'   => $this->order->reference ?? null,
            'order_date' => $this->order->order_date->toDateTimeString(),
            'name'       => $this->ingredient->name,
            'price'      => number_format($this->ingredient->price,2),
            'unit'       => $this->ingredient->unit,
            'image_url'  => get_media_url($this->ingredient->image_url),
            'status'     => $this->status,
            'vendor'     => $this->vendor ? [
                                'id'       => $this->vendor->id,
                                'name'     => $this->vendor->name,
                                'email'    => $this->vendor->email,
                                'phone_number'    => $this->vendor->phone_number ?? null,
                            ] : null,
        ];
    }
}
