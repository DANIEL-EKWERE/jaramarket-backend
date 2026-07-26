<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodIngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('food_ingredients')->insert([
            [
                'food_id' => '1',
                'ingredient_id' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'food_id' => '1',
                'ingredient_id' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'food_id' => '2',
                'ingredient_id' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

}
