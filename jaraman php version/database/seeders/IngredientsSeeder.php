<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class IngredientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('ingredients')->insert([
            [
                'id' => 1,
                'name' => 'Garri',
                'price' => 1800.00,
                'discounted_price' => 1600.00,
                'unit' => 'kg',
                'stock' => 100,
                'description' => 'High quality garri for making eba',
                'image_url' => 'https://img.freepik.com/free-psd/pile-fine-beige-sand-isolated-transparent-background_191095-86674.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Obu',
                'price' => 800.00,
                'discounted_price' => null,
                'unit' => 'kg',
                'stock' => 50,
                'description' => 'Fresh black-eyed peas',
                'image_url' => 'https://img.freepik.com/premium-photo/black-eyed-peas-wooden-bowl-isolated-white-background_33736-2909.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Mango',
                'price' => 300.00,
                'discounted_price' => 250.00,
                'unit' => 'piece',
                'stock' => 200,
                'description' => 'Fresh ripe mangoes',
                'image_url' => 'https://img.freepik.com/free-photo/delicious-mango-still-life_23-2151542130.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Apple',
                'price' => 500.00,
                'discounted_price' => null,
                'unit' => 'piece',
                'stock' => 150,
                'description' => 'Fresh red apples',
                'image_url' => 'https://img.freepik.com/free-psd/fresh-glistening-red-apple-with-leaf-transparent-background_84443-27689.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
