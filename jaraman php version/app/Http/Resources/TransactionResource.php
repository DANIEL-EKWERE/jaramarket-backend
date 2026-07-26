<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'reference'   => $this->reference,
            'type'        => $this->transaction_type,
            'amount'      => round($this->amount, 2),          // accessor already ÷100
            'old_balance' => round($this->old_balance / 100, 2),
            'new_balance' => round($this->new_balance / 100, 2),
            'currency'    => $this->currency ?? 'NGN',
            'comment'     => $this->comment,
            'is_refund'   => (bool) $this->is_refund,
            'status'      => $this->status ?? 'completed',
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
