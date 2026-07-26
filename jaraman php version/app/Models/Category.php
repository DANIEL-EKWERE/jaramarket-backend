<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Category extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [];

    protected $fillable = [
      'name', 'category_type_id', 'description', 'sort_by',
    ];

    public function category_type()
    {
      return $this->belongsTo(CategoryType::class);
    }

    public function products()
    {
      return $this->belongsToMany(Product::class, 'category_product', 'category_id', 'product_id');
    }
    
    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

}
