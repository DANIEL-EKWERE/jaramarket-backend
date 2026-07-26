<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentNow extends Model
{
    use HasFactory;

    protected $table = "payments";

    protected $fillable = [
        'user_id',
        'order_id',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'currency',
        'payment_date',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'metadata' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
