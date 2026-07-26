<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Seed default settings for storage and S3
        $defaults = [
            ['key' => 'storage_disk',        'value' => 'public'],
            ['key' => 's3_bucket',           'value' => ''],
            ['key' => 's3_region',           'value' => 'us-east-1'],
            ['key' => 's3_access_key',       'value' => ''],
            ['key' => 's3_secret_key',       'value' => ''],
            ['key' => 's3_url',              'value' => ''],
            ['key' => 's3_endpoint',         'value' => ''],
        ];

        foreach ($defaults as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'storage_disk', 's3_bucket', 's3_region',
            's3_access_key', 's3_secret_key', 's3_url', 's3_endpoint',
        ])->delete();
    }
};
