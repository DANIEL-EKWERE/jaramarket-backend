<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $response = [
            'id' => $this->id,
            'name' => $this->name,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'profile_picture' => $this->profile_picture ? asset($this->profile_picture) : null,
            'country' => $this->country_id ? new CountryResource($this->country) : null,
            'referral_code' => $this->referral_code,
            'referral_count' => $this->referrals()->count(),
            'created_at' => Carbon::parse($this->created_at)->toDateTimeString()
        ];

        return $response;
    }
}
