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
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('vendor_id')->after('unit')->nullable()->constrained('users')->onDelete('cascade');
            $table->datetime('vendor_at')->after('vendor_id')->nullable();
            $table->string('status')->after('vendor_at')->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['vendor_id','vendor_at','status']);
        });
    }
};
