<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStatePrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FoodService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $image_url = upload_image('food', $data['image_url'] ?? null);

            $product = Product::create([
                'name'              => $data['name'],
                'description'       => $data['description'] ?? null,
                'price'             => $data['price'],
                'discount_price'    => $data['discount_price'] ?? null,
                'preparation_steps' => $data['preparation_steps'] ?? null,
                'stock'             => $data['stock'] ?? 0,
                'rating'            => $data['rating'] ?? null,
                'image_url'         => $image_url,
            ]);

            if (!empty($data['categories'])) {
                $product->categories()->attach($data['categories']);
            }

            if (!empty($data['ingredients'])) {
                foreach ($data['ingredients'] as $ingredient) {
                    if (!empty($ingredient['ingredient_id'])) {
                        $product->ingredients()->attach($ingredient['ingredient_id'], [
                            'quantity' => $ingredient['quantity'],
                            'unit'     => $ingredient['unit'],
                        ]);
                    }
                }
            }

            $this->syncStatePrices($product, $data['state_prices'] ?? []);

            return $product;
        });
    }

    public function update(array $data, Product $product): Product
    {
        return DB::transaction(function () use ($data, $product) {
            $image_url = !empty($data['image_url'])
                ? upload_image('food', $data['image_url'], $product->image_url)
                : $product->image_url;

            $product->update([
                'name'              => $data['name'],
                'description'       => $data['description'] ?? null,
                'price'             => $data['price'],
                'discount_price'    => $data['discount_price'] ?? null,
                'preparation_steps' => $data['preparation_steps'] ?? null,
                'stock'             => $data['stock'] ?? $product->stock,
                'rating'            => $data['rating'] ?? $product->rating,
                'image_url'         => $image_url,
            ]);

            if (!empty($data['categories'])) {
                $product->categories()->sync($data['categories']);
            }

            if (!empty($data['ingredients'])) {
                $syncData = [];
                foreach ($data['ingredients'] as $ingredient) {
                    if (!empty($ingredient['ingredient_id'])) {
                        $syncData[$ingredient['ingredient_id']] = [
                            'quantity' => $ingredient['quantity'],
                            'unit'     => $ingredient['unit'],
                        ];
                    }
                }
                $product->ingredients()->sync($syncData);
            }

            $this->syncStatePrices($product, $data['state_prices'] ?? []);

            return $product;
        });
    }

    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            if ($product->image_url) {
                delete_image($product->image_url);
            }
            $product->categories()->detach();
            $product->ingredients()->detach();
            $product->statePrices()->delete();
            $product->delete();

            return true;
        });
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function syncStatePrices(Product $product, array $statePrices): void
    {
        // Build a keyed map of incoming state prices
        $incoming = collect($statePrices)
            ->filter(fn ($sp) => !empty($sp['state_id']))
            ->keyBy('state_id');

        // Delete removed state prices
        $product->statePrices()
            ->whereNotIn('state_id', $incoming->keys()->toArray())
            ->delete();

        // Upsert each incoming state price
        foreach ($incoming as $stateId => $sp) {
            $product->statePrices()->updateOrCreate(
                ['state_id' => $stateId],
                [
                    'price'          => $sp['price'],
                    'discount_price' => $sp['discount_price'] ?? null,
                ]
            );
        }
    }
}
