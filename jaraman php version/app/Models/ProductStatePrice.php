<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductStatePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'state_id',
        'price',
        'discount_price',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'discount_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
