<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Uom extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name'];

    /**
     * Get the product that owns the step.
     */
    public function ingredients()
    {
        return $this->hasMany(Ingredient::class, 'unit', 'code');
    }
}
