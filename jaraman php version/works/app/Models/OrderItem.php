<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'ingredient_id',
        'quantity',
        'price',
        'unit',
        'vendor_id',
        'vendor_at',
        'status',
        'assurance_user_id',
        'assurance_at',
        'pass_quality_assurance',
        'remark',
        're_assigned',
        'amount',
        'vendor_amount',
        'commision',
        'referral',
        'referral_id'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
    
    public function quality_assurance_user()
    {
        return $this->belongsTo(User::class, 'assurance_user_id');
    }
}
