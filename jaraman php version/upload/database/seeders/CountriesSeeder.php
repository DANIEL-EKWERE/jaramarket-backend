<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    public function run()
    {
        $json = file_get_contents(storage_path('app/countries.json'));
        $countries = json_decode($json, true);

        foreach ($countries as $country) {
            DB::table('countries')->insert([
                'name' => $country['country'],
                'code' => '',
            ]);
        }
    }
}