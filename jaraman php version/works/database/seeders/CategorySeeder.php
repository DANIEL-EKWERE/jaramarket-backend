<?php

namespace Database\Seeders;


use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'id' => 1,
                 'name' => 'Cabohydrate',
                 'description' => 'nil',
                 'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Protein',
                'description' => 'nil',
                 'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Vitamin',
                'description' => 'nil',
                 'created_at' => now(),
                'updated_at' => now(),
            ],


        ]);
    }
}
