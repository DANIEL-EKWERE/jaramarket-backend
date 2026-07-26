<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('category_types')->insert([
            [
                 'id' => 1,
                 'name' => 'Food',
                 'created_at' => now(),
                 'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Vendor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
