<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Get the product that owns the step.
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }
}
