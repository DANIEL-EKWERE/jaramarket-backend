<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_picture')->after('role')->nullable();
            $table->integer('country_id')->after('profile_picture')->nullable();
            $table->string('business_name')->after('country_id')->nullable();
            $table->text('business_address')->after('business_name')->nullable();
            $table->string('shop_size')->after('business_address')->nullable();
            $table->decimal('latitude',10,7)->after('shop_size')->nullable();
            $table->decimal('longitude',10,7)->after('latitude')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
