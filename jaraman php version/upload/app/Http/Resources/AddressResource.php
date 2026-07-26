<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'country'         => $this->country->name,
            'state'           => $this->state->name,
            'lga'             => $this->lga->name,
            'contact_address' => $this->contact_address,
            'phone_number'    => $this->phone_number,
            'is_default'      => $this->is_default,
            'created_at'      => Carbon::parse($this->created_at)->toDateTimeString(),
        ];
    }
}
