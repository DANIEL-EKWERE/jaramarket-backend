<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Ingredient extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'description',
        'price',
        'discounted_price',
        'unit',
        'stock',
        'image_url',
        'category_id',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'stock'            => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function products()
    {
        return $this->belongsToMany(Product::class, 'ingredient_product', 'ingredient_id', 'product_id')
            ->withPivot('quantity', 'unit')
            ->withTimestamps();
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'unit', 'code');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function statePrices()
    {
        return $this->hasMany(IngredientStatePrice::class);
    }

    public function lgaPrices()
    {
        return $this->hasMany(IngredientLgaPrice::class);
    }

    // ─── Price resolution ─────────────────────────────────────────────────────

    /**
     * Resolve effective price using the full priority chain:
     *
     *   1. LGA price   (most specific — customer's local government area)
     *   2. State price (regional fallback)
     *   3. Default     (ingredient table price — global fallback)
     *
     * @param  int|null  $lgaId    Customer's LGA ID
     * @param  int|null  $stateId  Customer's State ID (used as fallback if no LGA price)
     * @return array{price: string, discounted_price: string|null, price_source: string}
     */
    public function getPriceForLocation(?int $lgaId, ?int $stateId = null): array
    {
        // 1️⃣  LGA price (highest priority)
        if ($lgaId) {
            $lgaPrice = $this->lgaPrices->firstWhere('lga_id', $lgaId);

            if ($lgaPrice) {
                return [
                    'price'            => $lgaPrice->price,
                    'discounted_price' => $lgaPrice->discounted_price,
                    'price_source'     => 'lga',
                ];
            }
        }

        // 2️⃣  State price (regional fallback)
        if ($stateId) {
            $statePrice = $this->statePrices->firstWhere('state_id', $stateId);

            if ($statePrice) {
                return [
                    'price'            => $statePrice->price,
                    'discounted_price' => $statePrice->discounted_price,
                    'price_source'     => 'state',
                ];
            }
        }

        // 3️⃣  Default ingredient price
        return [
            'price'            => $this->price,
            'discounted_price' => $this->discounted_price,
            'price_source'     => 'default',
        ];
    }

    /**
     * Legacy helper — state-only resolution (kept for backward compatibility).
     */
    public function getPriceForState(?int $stateId): array
    {
        return $this->getPriceForLocation(null, $stateId);
    }
}
