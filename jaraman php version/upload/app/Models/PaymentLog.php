<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function owner()
    {
        return $this->morphTo('transaction_owner', 'transaction_owner_type', 'transaction_owner_id');
    }

    public function initiator()
    {
        return $this->morphTo('transaction_initiator', 'transaction_initiator_type', 'transaction_initiator_id')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'transaction_owner_id');
    }
}
