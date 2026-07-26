<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_date',
        'reference',
        'user_id',
        'address_id',
        'delivery_type',
        'shipping_fee',
        'service_charge',
        'vat',
        'total',
        'status',
        'remarks',
        'audio'
    ];

    protected $casts = [
        'order_date' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
