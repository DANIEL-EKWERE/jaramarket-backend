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
            $table->decimal('amount', 10, 2)->after('re_assigned')->default(0);
            $table->decimal('vendor_amount', 10, 2)->after('amount')->default(0);
            $table->decimal('commision', 10, 2)->after('vendor_amount')->default(0);
            $table->decimal('referral', 10, 2)->after('commision')->default(0);
            $table->foreignId('referral_id')->after('referral')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['amount','vendor_ammount','commision','referral','referral_id']);
        });
    }
};
