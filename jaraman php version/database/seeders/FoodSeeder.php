<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('foods')->insert([
            [
                'id' => 1,
                'name' => 'Afang',
                'category_id' => '1',
                'description' => 'nil',
                'price' => '6800',
                'image_url' => 'https://img.freepik.com/free-psd/pile-fine-beige-sand-isolated-transparent-background_191095-86674.jpg?t=st=1741159817~exp=1741163417~hmac=049ad381c7510d91db99e6720a68fe6fef5c3983f89761a19d6752b1a6d05750&w=740',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Egusi',
                'category_id' => '2',
                'description' => 'nil',
                'price' => '9800',
                'image_url' => '180https://img.freepik.com/premium-photo/black-eyed-peas-wooden-bowl-isolated-white-background_33736-2909.jpg?w=7400',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'White Soup',
                'category_id' => '3',
                'description' => 'nil',
                'price' => '10800',
                'image_url' => 'https://img.freepik.com/free-photo/delicious-mango-still-life_23-2151542130.jpg?t=st=1741159925~exp=1741163525~hmac=c668d7171c5b1fd8e8c4e06dcbe34d5100ce360f43953d0da5b8337be0b14e84&w=1060',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => 4,
                'name' => 'Rice',
                'category_id' => '3',
                'description' => 'nil',
                'price' => '11800',
                'image_url' => 'https://img.freepik.com/free-psd/fresh-glistening-red-apple-with-leaf-transparent-background_84443-27689.jpg?t=st=1741159983~exp=1741163583~hmac=03268953d6d1d4560eceeb626eb3cc097cfee516329b8f485cdb542f23deae4d&w=740',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
