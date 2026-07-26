<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'description'];

    /**
     * Get the product that owns the step.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
