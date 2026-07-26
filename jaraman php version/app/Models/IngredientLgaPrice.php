<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientLgaPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'lga_id',
        'price',
        'discounted_price',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'discounted_price' => 'decimal:2',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }
}
