<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        
        return [
            'id'             => $this->id,
            'order_date'     => $this->order_date,
            'delivery_type'  => $this->delivery_type,
            'shipping_fee'   => $this->shipping_fee,
            'service_charge' => $this->service_charge,
            'vat'            => $this->vat,
            'total'          => $this->total,
            'status'         => $this->status,
            'address'        => new AddressResource($this->whenLoaded('address')),
            'products'       => $this->whenLoaded('items', function () {
                                return $this->items
                                ->whereNotNull('product_id')
                                ->map(fn($item) => [
                                    'id' => $item->product->id,
                                    'name' => $item->product->name,
                                    'price' => number_format($item->product->price,2),
                                    'image_url' => get_media_url($item->product->image_url),
                                    'status' => $item->status,
                                ])
                                ->unique('id') 
                                ->values();
             }),
            'ingredients'    => $this->whenLoaded('items', function () {
                                return $this->items
                                    ->whereNotNull('ingredient_id')
                                    ->map(fn($item) => [
                                        'id' => $item->ingredient->id,
                                        'name' => $item->ingredient->name,
                                        'price' => number_format($item->ingredient->price,2),
                                        'unit' => $item->ingredient->unit,
                                        'image_url' => get_media_url($item->ingredient->image_url),
                                        'status'  => $item->status
                                    ])
                                    ->values();
             }),
             'remarks'        => $this->remarks,
             'audio_url'      => get_media_url($this->audio),
             'created_at'     => $this->created_at->toDateTimeString(),
        ];
    }
}
