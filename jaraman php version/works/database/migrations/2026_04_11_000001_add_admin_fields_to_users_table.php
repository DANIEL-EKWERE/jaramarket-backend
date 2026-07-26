<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'state_id')) {
                $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete()->after('country_id');
            }
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('users', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['state_id', 'fcm_token', 'is_verified']);
        });
    }
};
