<?php

namespace App\Http\Resources\StatesAndLga;

use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
        ];

        if ($this->relationLoaded('lgas')) {
            $data['lgas'] = LgaResource::collection($this->lgas);
        }

        return $data;
    }
}
