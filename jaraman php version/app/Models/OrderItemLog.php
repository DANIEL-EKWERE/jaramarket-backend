<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OrderItemLog extends Model
{
    protected $fillable = [
        'order_item_id', 'vendor_id', 'status', 'changed_at'
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}