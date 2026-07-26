<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IngredientStatePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'state_id',
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

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
