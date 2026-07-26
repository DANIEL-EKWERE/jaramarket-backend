<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountriesSeeder::class,
            StateAndLgaSeeder::class,
            CategoryTypeSeeder::class,
            CategorySeeder::class,
            UomSeeder::class,
            IngredientsSeeder::class,
            FoodSeeder::class,
            FoodIngredientSeeder::class,
            Userseeder::class,
            PermissionSeeder::class,
        ]);
    }
}
