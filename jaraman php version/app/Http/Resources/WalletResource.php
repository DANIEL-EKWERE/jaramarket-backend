<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'balance'         => number_format((float) $this->balance, 2),
            'balance_raw'     => (float) $this->balance,
            'currency'        => 'NGN',
            'last_updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
