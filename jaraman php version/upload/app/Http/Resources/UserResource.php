<?php

namespace App\Http\Resources;

use App\Enums\UserPermissionsEnum;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $this->whenLoaded('categories');

        $response = [
            'id' => $this->id,
            'name' => $this->name,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'profile_picture' => $this->profile_picture ? asset($this->profile_picture) : null,
            'country' => $this->country_id ? new CountryResource($this->country) : null,
            'business_name' => $this->business_name,
            'business_address' => $this->business_address,
            'shop_size' => $this->shop_size,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'bank_name' => $this->bank_name,
            'bank_code' => $this->bank_code,
            'payment_method' => $this->payment_method,
            'email_verified' => ! is_null($this->email_verified_at),
            'role' => $this->role,
            'is_vendor' => $this->role === UserPermissionsEnum::VENDOR(),
            'vendor_categories' => $this->role === UserPermissionsEnum::VENDOR() && $this->categories ? VendorCategoryResource::collection($this->categories) : null,
            'referral_code' => $this->referral_code,
            'referrer_id' => $this->referrer_id,
            'referral_count' => $this->referral_count,
            'has_pin' => $this->pin !== null,
            'is_active' => $this->is_active,
            'created_at' => Carbon::parse($this->created_at)->toDateTimeString(),
            'last_login' => Carbon::parse($this->last_login)->toDateTimeString(),
            'wallet'  => new WalletResource($this->wallet),
            'favorites' =>  FavoriteResource::collection($this->favorites),
            'contact_address' =>  AddressResource::collection($this->addresses),
        ];

        return $response;
    }
}
