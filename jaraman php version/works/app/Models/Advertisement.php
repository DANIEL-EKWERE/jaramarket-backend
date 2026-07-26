<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'image', 'type', 'value', 'ingredient_ids', 'status'
    ];

    protected $casts = [
        'ingredient_ids' => 'array',
    ];

    public function getIngredientsAttribute()
    {
        return Ingredient::whereIn('id', $this->ingredient_ids ?? [])->get();
    }
}
