<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientLgaPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IngredientService
{
    public function create(array $data): Ingredient
    {
        return DB::transaction(function () use ($data) {
            $image_url = !empty($data['image_url'])
                ? upload_image('ingredients', $data['image_url'])
                : null;

            $ingredient = Ingredient::create([
                'name'             => $data['name'],
                'description'      => $data['description'] ?? null,
                'price'            => $data['price'],
                'discounted_price' => $data['discounted_price'] ?? null,
                'unit'             => $data['unit'],
                'stock'            => $data['stock'] ?? 0,
                'category_id'      => $data['category_id'] ?? null,
                'image_url'        => $image_url,
            ]);

            $this->syncStatePrices($ingredient, $data['state_prices'] ?? []);
            $this->syncLgaPrices($ingredient, $data['lga_prices'] ?? []);

            return $ingredient;
        });
    }

    public function update(array $data, Ingredient $ingredient): Ingredient
    {
        return DB::transaction(function () use ($data, $ingredient) {
            $image_url = !empty($data['image_url'])
                ? upload_image('ingredients', $data['image_url'], $ingredient->image_url)
                : $ingredient->image_url;

            $ingredient->update([
                'name'             => $data['name'],
                'description'      => $data['description'] ?? null,
                'price'            => $data['price'],
                'discounted_price' => $data['discounted_price'] ?? null,
                'unit'             => $data['unit'],
                'stock'            => $data['stock'] ?? $ingredient->stock,
                'category_id'      => $data['category_id'] ?? null,
                'image_url'        => $image_url,
            ]);

            $this->syncStatePrices($ingredient, $data['state_prices'] ?? []);
            $this->syncLgaPrices($ingredient, $data['lga_prices'] ?? []);

            return $ingredient;
        });
    }

    public function delete(Ingredient $ingredient): bool
    {
        return DB::transaction(function () use ($ingredient) {
            if ($ingredient->image_url) {
                delete_image($ingredient->image_url);
            }
            $ingredient->statePrices()->delete();
            $ingredient->lgaPrices()->delete();
            $ingredient->products()->detach();
            $ingredient->delete();

            return true;
        });
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function syncLgaPrices(Ingredient $ingredient, array $lgaPrices): void
    {
        $incoming = collect($lgaPrices)
            ->filter(fn ($lp) => !empty($lp['lga_id']))
            ->keyBy('lga_id');

        // Remove LGA prices not in the incoming list
        $ingredient->lgaPrices()
            ->whereNotIn('lga_id', $incoming->keys()->toArray())
            ->delete();

        // Upsert each entry
        foreach ($incoming as $lgaId => $lp) {
            $ingredient->lgaPrices()->updateOrCreate(
                ['lga_id' => $lgaId],
                [
                    'price'            => $lp['price'],
                    'discounted_price' => $lp['discounted_price'] ?? null,
                ]
            );
        }
    }

    private function syncStatePrices(Ingredient $ingredient, array $statePrices): void
    {
        $incoming = collect($statePrices)
            ->filter(fn ($sp) => !empty($sp['state_id']))
            ->keyBy('state_id');

        // Remove state prices not in the incoming list
        $ingredient->statePrices()
            ->whereNotIn('state_id', $incoming->keys()->toArray())
            ->delete();

        // Upsert each entry
        foreach ($incoming as $stateId => $sp) {
            $ingredient->statePrices()->updateOrCreate(
                ['state_id' => $stateId],
                [
                    'price'            => $sp['price'],
                    'discounted_price' => $sp['discounted_price'] ?? null,
                ]
            );
        }
    }
}
