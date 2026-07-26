<?php

namespace App\Models;

use App\Traits\AddPipelineToModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, AddPipelineToModelTrait;

    protected $hidden = ['pivot'];

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'discount_price',
        'price',
        'preparation_steps',
        'rating',
        'stock',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'discount_price' => 'decimal:2',
        'rating'         => 'decimal:1',
        'stock'          => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'ingredient_product', 'product_id', 'ingredient_id')
            ->withPivot('quantity', 'unit')
            ->withTimestamps();
    }

    public function statePrices()
    {
        return $this->hasMany(ProductStatePrice::class);
    }

    // ─── Price Resolution ─────────────────────────────────────────────────────

    /**
     * Resolve effective price with full 3-tier fallback:
     *   1. State price (regional override)
     *   2. Default product price (global fallback)
     *
     * Note: Products use state-level pricing (not LGA-level).
     * Ingredients use the more granular LGA → State → Default chain.
     *
     * @param  int|null $stateId  Customer's state ID
     * @return array{price: string, discount_price: string|null, price_source: string}
     */
    public function getPriceForLocation(?int $stateId = null): array
    {
        if ($stateId) {
            $statePrice = $this->statePrices->firstWhere('state_id', $stateId);

            if ($statePrice) {
                return [
                    'price'          => $statePrice->price,
                    'discount_price' => $statePrice->discount_price,
                    'price_source'   => 'state',
                ];
            }
        }

        return [
            'price'          => $this->price,
            'discount_price' => $this->discount_price,
            'price_source'   => 'default',
        ];
    }

    /**
     * Legacy alias — kept for backward compatibility.
     */
    public function getPriceForState(?int $stateId): array
    {
        return $this->getPriceForLocation($stateId);
    }

    // ─── Ingredient-based price calculation ───────────────────────────────────

    public function calculatePrice(): float
    {
        $total = 0;
        foreach ($this->ingredients as $ingredient) {
            $convertedQuantity = $this->convertToBaseUnit(
                $ingredient->pivot->quantity,
                $ingredient->pivot->unit
            );
            $total += $convertedQuantity * ($ingredient->price_per_unit ?? $ingredient->price);
        }
        return $total;
    }

    private function convertToBaseUnit(float $quantity, string $unit): float
    {
        $rates = [
            'kg' => 1, 'g' => 0.001, 'l' => 1, 'ml' => 0.001,
            'piece' => 1, 'cup' => 0.25, 'tbsp' => 0.015, 'tsp' => 0.005,
        ];
        return $quantity * ($rates[$unit] ?? 1);
    }
}
